<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Product;
use App\Models\BillerCommission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateBillerCommission extends Command
{
    /**
     * php artisan commission:recalculate            -> dry run, shows what would change
     * php artisan commission:recalculate --apply     -> actually writes the fix
     * php artisan commission:recalculate --apply --sale=123  -> fix a single sale (testing)
     */
    protected $signature = 'commission:recalculate
                            {--apply : Actually write changes. Without this flag, it only reports.}
                            {--sale= : Limit to a single sale ID, useful for testing first.}';

    protected $description = 'Recalculate total_profit / total_commission for sales, fixing the missing-quantity bug';

    public function handle()
    {
        $apply = $this->option('apply');
        $saleId = $this->option('sale');

        // Only touch sales whose commission hasn't been paid out yet,
        // and whose currently-recorded profit isn't negative (i.e. skip
        // already-settled or already-negative commission records).
        // Written as a plain whereIn/subquery join so it doesn't depend on
        // a billerCommission() relationship existing on the Sale model.
        $eligibleSaleIds = BillerCommission::query()
            ->where('is_paid', 0)
            ->where('total_profit', '>=', 0)
            ->pluck('sale_id');

        $query = Sale::query()
            ->whereNull('deleted_at')
            ->whereIn('id', $eligibleSaleIds);

        if ($saleId) {
            $query->where('id', $saleId);
        }

        $count = $query->count();
        $this->info(($apply ? 'APPLYING' : 'DRY RUN') . " — processing {$count} sale(s)...");

        $changed = 0;
        $unchanged = 0;

        $query->orderBy('id')->chunkById(200, function ($sales) use (&$changed, &$unchanged, $apply) {
            foreach ($sales as $sale) {
                $lines = Product_Sale::where('sale_id', $sale->id)->get();

                if ($lines->isEmpty()) {
                    continue;
                }

                // Preload products used in this sale to avoid N+1 queries
                $productIds = $lines->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $totalProfit = 0;
                $totalCommission = 0;
                $totalItems = 0;

                foreach ($lines as $line) {
                    $product = $products->get($line->product_id);
                    if (!$product) {
                        continue; // product deleted since sale — skip line, can't recompute
                    }

                    $qty = (float) $line->qty;
                    $profitPerUnit = (float) $line->net_unit_price - (float) $product->price;
                    $lineProfit = $profitPerUnit * $qty;
                    $lineCommission = $lineProfit > 0 ? $lineProfit * 0.10 : 0;

                    $totalProfit += $lineProfit;
                    $totalCommission += $lineCommission;
                    $totalItems += $qty; // switch to +1 per line if you want "line count" instead of "unit count"
                }

                $oldProfit = (float) $sale->total_profit;
                $oldCommission = (float) $sale->total_commission;

                // Only touch rows that actually differ (accounting for float rounding)
                if (abs($oldProfit - $totalProfit) < 0.01 && abs($oldCommission - $totalCommission) < 0.01) {
                    $unchanged++;
                    continue;
                }

                $changed++;
                $this->line(sprintf(
                    "Sale #%d (%s): profit %.2f -> %.2f | commission %.2f -> %.2f",
                    $sale->id,
                    $sale->reference_no,
                    $oldProfit,
                    $totalProfit,
                    $oldCommission,
                    $totalCommission
                ));

                if ($apply) {
                    DB::transaction(function () use ($sale, $totalProfit, $totalCommission, $totalItems) {
                        $sale->update([
                            'total_profit' => $totalProfit,
                            'total_commission' => $totalCommission,
                        ]);

                        BillerCommission::where('sale_id', $sale->id)->update([
                            'total_items' => $totalItems,
                            'total_profit' => $totalProfit,
                            'commission_amount' => $totalCommission,
                            'calculated_at' => now(),
                        ]);
                    });
                }
            }
        });

        $this->info("Done. Changed: {$changed}, Unchanged: {$unchanged}.");
        if (!$apply) {
            $this->comment('This was a dry run. Re-run with --apply to write changes.');
        }

        return 0;
    }
}
