<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Models\{SaleExchange, ProductExchange, Customer, Warehouse, Biller, CustomField, GeneralSetting, Product, Product_Sale, Product_Warehouse};
use App\Models\{ProductBatch, ProductVariant, Sale, Tax, Unit};
use Illuminate\Support\Facades\{Auth, DB, Log, Validator};
use Spatie\Permission\Models\Role;

class ExchangeController extends Controller
{
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('returns-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            $warehouse_id = $request->input('warehouse_id', 0);

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date   = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime('-1 year'));
                $ending_date   = date("Y-m-d");
            }

            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $general_setting     = GeneralSetting::latest()->first();

            return view('backend.sale-exchange.index', compact(
                'starting_date',
                'ending_date',
                'warehouse_id',
                'all_permission',
                'lims_warehouse_list',
                'general_setting'
            ));
        }

        return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function exchangeData(Request $request)
    {
        $columns      = [1 => 'created_at', 2 => 'reference_no'];
        $warehouse_id = $request->input('warehouse_id');
        $query        = SaleExchange::query();

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $query->where('user_id', Auth::id());
        } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
            $query->where('warehouse_id', Auth::user()->warehouse_id);
        } elseif ($warehouse_id != 0) {
            $query->where('warehouse_id', $warehouse_id);
        }

        $query->whereDate('created_at', '>=', $request->input('starting_date'))
            ->whereDate('created_at', '<=', $request->input('ending_date'));

        $totalData     = $query->count();
        $totalFiltered = $totalData;
        $limit         = $request->input('length') != -1 ? $request->input('length') : $totalData;
        $start         = $request->input('start');
        $order         = 'sale_exchanges.' . $columns[$request->input('order.0.column')];
        $dir           = $request->input('order.0.dir');

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->join('customers', 'sale_exchanges.customer_id', '=', 'customers.id')
                ->join('billers', 'sale_exchanges.biller_id', '=', 'billers.id')
                ->select('sale_exchanges.*')
                ->where(function ($q) use ($search) {
                    $q->where('sale_exchanges.reference_no', 'LIKE', "%{$search}%")
                        ->orWhere('customers.name', 'LIKE', "%{$search}%")
                        ->orWhere('customers.phone_number', 'LIKE', "%{$search}%")
                        ->orWhere('billers.name', 'LIKE', "%{$search}%")
                        ->orWhereDate('sale_exchanges.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))));
                });
            $totalFiltered = $query->count();
        }

        $exchanges = $query->with(['biller', 'customer', 'warehouse', 'user', 'sale'])
            ->offset($start)->limit($limit)->orderBy($order, $dir)->get();

        $data = [];
        foreach ($exchanges as $key => $exchange) {
            $saleReference = ($exchange->sale_id && $exchange->sale) ? $exchange->sale->reference_no : 'N/A';
            $data[] = [
                'key'            => $key,
                'date'           => date(config('date_format'), strtotime($exchange->created_at->toDateString())),
                'reference_no'   => $exchange->reference_no,
                'sale_reference' => $saleReference,
                'warehouse'      => $exchange->warehouse->name,
                'biller'         => $exchange->biller->name,
                'customer'       => $exchange->customer->name,
                'payment_type'   => $exchange->payment_type == 'pay'
                    ? '<span class="badge badge-danger">Pay</span>'
                    : '<span class="badge badge-success">Receive</span>',
                'amount'         => number_format($exchange->amount, config('decimal')),
                'options'        => $this->buildActionButtons($exchange, $request['all_permission']),
                'exchange'       => json_encode([
                    date(config('date_format'), strtotime($exchange->created_at->toDateString())),
                    $exchange->reference_no,
                    $exchange->warehouse->name,
                    $exchange->biller->name,
                    $exchange->biller->company_name ?? '',
                    $exchange->biller->email,
                    $exchange->biller->phone_number,
                    $exchange->biller->address,
                    $exchange->biller->city,
                    $exchange->customer->name,
                    $exchange->customer->phone_number,
                    $exchange->customer->address,
                    $exchange->customer->city,
                    $exchange->id,
                    $exchange->total_tax,
                    $exchange->total_discount,
                    $exchange->amount,
                    $exchange->order_tax,
                    $exchange->order_tax_rate,
                    $exchange->grand_total,
                    nl2br($exchange->exchange_note ?? ''),
                    nl2br($exchange->staff_note ?? ''),
                    $exchange->user->name,
                    $exchange->user->email,
                    $saleReference,
                    $exchange->document,
                    config('currency', 'BDT'),
                    $exchange->exchange_rate ?? '',
                    $exchange->payment_type ?? '',
                ]),
            ];
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
        ]);
    }

    private function buildActionButtons($exchange, $permissions)
    {
        $html = '<div class="btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
              ' . __("db.action") . ' <span class="caret"></span>
            </button>
            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default">
                <li><button type="button" class="btn btn-link view"><i class="fa fa-eye"></i> ' . __('db.View') . '</button></li>';

        if (in_array("exchanges-edit", $permissions)) {
            $html .= '<li><a href="' . route('exchange.edit', $exchange->id) . '" class="btn btn-link">
                <i class="dripicons-document-edit"></i> ' . __('db.edit') . '</a></li>';
        }
        if (in_array("exchanges-delete", $permissions)) {
            $html .= '<form action="' . route("exchange.destroy", $exchange->id) . '" method="POST" class="delete-form">'
                . csrf_field() . method_field("DELETE")
                . '<li><button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                    <i class="dripicons-trash"></i> ' . __("db.delete") . '
                </button></li></form>';
        }

        return $html . '</ul></div>';
    }

    public function productExchange($id)
    {
        try {
            $exchange     = SaleExchange::with(['products.product', 'products.saleUnit'])->findOrFail($id);
            $productsData = ['new' => [], 'returned' => []];

            foreach ($exchange->products as $item) {
                $info = [
                    'name'       => $item->product->name,
                    'code'       => $item->product->code,
                    'name_code'  => $item->product->name . ' [' . $item->product->code . ']',
                    'batch_no'   => $item->product->batch_no ?? 'N/A',
                    'qty'        => $item->qty,
                    'unit_code'  => $item->saleUnit->unit_code ?? '',
                    'unit_price' => number_format($item->net_unit_price, config('decimal')),
                    'tax'        => number_format($item->tax, config('decimal')),
                    'tax_rate'   => $item->tax_rate,
                    'discount'   => number_format($item->discount, config('decimal')),
                    'subtotal'   => number_format($item->total, config('decimal')),
                    'type'       => $item->type,
                ];
                $productsData[$item->type === 'new' ? 'new' : 'returned'][] = $info;
            }

            $productsData['totals'] = [
                'new'            => number_format($exchange->products->where('type', 'new')->sum('total'), config('decimal')),
                'returned'       => number_format($exchange->products->where('type', 'returned')->sum('total'), config('decimal')),
                'tax'            => number_format($exchange->total_tax, config('decimal')),
                'discount'       => number_format($exchange->total_discount, config('decimal')),
                'amount'         => number_format($exchange->amount, config('decimal')),
                'order_tax'      => number_format($exchange->order_tax, config('decimal')),
                'order_tax_rate' => $exchange->order_tax_rate,
                'grand_total'    => number_format($exchange->grand_total, config('decimal')),
            ];

            return response()->json($productsData);
        } catch (\Exception $e) {
            Log::error('Exchange product fetch error: ' . $e->getMessage());
            return response()->json(['error' => 'Exchange not found'], 404);
        }
    }

    public function create(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('exchange-add')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $lims_customer_list  = Customer::where('is_active', true)->get();
        $lims_account_list   = Account::latest()->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_biller_list    = Biller::where('is_active', true)->get();
        $lims_tax_list       = Tax::where('is_active', true)->get();
        $numberOfInvoice     = Sale::whereNull('deleted_at')->count();
        $general_setting     = GeneralSetting::latest()->first();
        $custom_fields       = CustomField::where('belongs_to', 'sale')->get();

        $lims_sale_data         = null;
        $lims_product_sale_data = null;

        if ($request->reference_no) {
            $lims_sale_data = Sale::where('reference_no', $request->reference_no)->first();
            if ($lims_sale_data) {
                $lims_product_sale_data = Product_Sale::with(['product', 'unit', 'productBatch'])
                    ->where('sale_id', $lims_sale_data->id)->get();
            }
        }

        $currency_exchange_rate = ($lims_sale_data && $lims_sale_data->exchange_rate)
            ? $lims_sale_data->exchange_rate : 1;

        // BUG FIX #3: $currency must be a flat array — JS accesses currency['exchange_rate'] directly
        $currency = [
            'id'            => 1,
            'exchange_rate' => (float) $currency_exchange_rate,
            'symbol'        => $general_setting->currency_symbol ?? '$',
        ];

        return view('backend.sale-exchange.create', compact(
            'lims_account_list',
            'lims_customer_list',
            'lims_warehouse_list',
            'lims_biller_list',
            'lims_tax_list',
            'lims_sale_data',
            'lims_product_sale_data',
            'currency_exchange_rate',
            'custom_fields',
            'numberOfInvoice',
            'general_setting',
            'currency'
        ));
    }

    public function store(Request $request)
    {
        // BUG FIX #5: sale_id is only set if the user actually searched — guard here
        if (!$request->input('sale_id')) {
            return redirect()->back()
                ->with('not_permitted', 'Please search and select a sale reference before submitting.')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $request->except([
                'document',
                'total_sale_discount',
                'type',
                'product_id',
                'product_code',
                'product_batch_id',
                'imei_number',
                'qty',
                'sale_unit',
                'net_unit_price',
                'discount',
                'tax_rate',
                'tax',
                'subtotal',
                'product_sale_id',
                'is_exchange',
                'return_note',
                'customer_id_hidden',
                'warehouse_id_hidden',
                'biller_id_hidden',
            ]);

            $data['reference_no']   = 'exc-' . date("Ymd") . '-' . date("his");
            $data['total_discount'] = $request->total_sale_discount ?? 0;
            $data['user_id']        = Auth::id();
            $data['exchange_note']  = $request->return_note ?? null;

            $lims_sale_data = Sale::whereNull('deleted_at')->find($request->input('sale_id'));
            if (!$lims_sale_data) {
                DB::rollBack();
                return redirect()->back()
                    ->with('not_permitted', 'Original sale not found.')
                    ->withInput();
            }

            $data['customer_id']  = $lims_sale_data->customer_id;
            $data['warehouse_id'] = $lims_sale_data->warehouse_id;
            $data['biller_id']    = $lims_sale_data->biller_id;

            // Document upload
            $document = $request->file('document');
            if ($document) {
                $v = Validator::make(
                    ['extension' => strtolower($document->getClientOriginalExtension())],
                    ['extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt']
                );
                if ($v->fails()) {
                    DB::rollBack();
                    return redirect()->back()->withErrors($v->errors())->withInput();
                }
                $ext          = $document->getClientOriginalExtension();
                $documentName = date("Ymdhis");
                $documentName = config('database.connections.saleprosaas_landlord')
                    ? $this->getTenantId() . '_' . $documentName . '.' . $ext
                    : $documentName . '.' . $ext;
                $document->move(public_path('documents/exchange'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_exchange_data = SaleExchange::create($data);

            // All product line arrays
            $type_array       = $request->input('type', []);
            $product_ids      = $request->input('product_id', []);
            $product_codes    = $request->input('product_code', []);
            $product_batch_id = $request->input('product_batch_id', []);
            $imei_number      = $request->input('imei_number', []);
            $qty              = $request->input('qty', []);
            $sale_unit        = $request->input('sale_unit', []);
            $net_unit_price   = $request->input('net_unit_price', []);
            $discount         = $request->input('discount', []);
            $tax_rate         = $request->input('tax_rate', []);
            $tax              = $request->input('tax', []);
            $total            = $request->input('subtotal', []);
            $product_sale_id  = $request->input('product_sale_id', []);
            $is_exchange      = $request->input('is_exchange', []); // codes of checked checkboxes

            $new_count      = 0;
            $returned_count = 0;

            foreach ($product_ids as $index => $id) {
                // BUG FIX #1: partial emits type="return", NOT "returned"
                $product_type = $type_array[$index] ?? 'new';

                if ($product_type === 'return') {
                    $code = $product_codes[$index] ?? null;
                    if (!$code || !in_array($code, $is_exchange)) {
                        continue; // unchecked — do not return stock
                    }

                    $original_ps = !empty($product_sale_id[$index])
                        ? Product_Sale::find($product_sale_id[$index])
                        : null;

                    $this->processReturnProduct(
                        $id,
                        $index,
                        $lims_exchange_data->id,
                        $data['warehouse_id'],
                        $qty,
                        $sale_unit,
                        $net_unit_price,
                        $discount,
                        $tax_rate,
                        $tax,
                        $total,
                        $product_codes,
                        $product_batch_id,
                        $imei_number,
                        $original_ps
                    );
                    $returned_count++;
                } elseif ($product_type === 'new') {
                    $this->processNewProduct(
                        $id,
                        $index,
                        $lims_exchange_data->id,
                        $data['warehouse_id'],
                        $qty,
                        $sale_unit,
                        $net_unit_price,
                        $discount,
                        $tax_rate,
                        $tax,
                        $total,
                        $product_codes,
                        $product_batch_id,
                        $imei_number
                    );
                    $new_count++;
                }
            }

            DB::commit();

            return redirect('exchange')->with(
                'message',
                "Exchange created — {$new_count} new product(s), {$returned_count} returned product(s)."
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exchange Store Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()
                ->with('not_permitted', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function processNewProduct(
        $product_id,
        $index,
        $exchange_id,
        $warehouse_id,
        $qty,
        $sale_unit,
        $net_unit_price,
        $discount,
        $tax_rate,
        $tax,
        $total,
        $product_code,
        $product_batch_id,
        $imei_number
    ) {
        $product      = Product::findOrFail($product_id);
        $sale_unit_id = 0;
        $quantity     = (float) $qty[$index];

        if (!empty($sale_unit[$index]) && $sale_unit[$index] !== 'n/a') {
            $unit = Unit::where('unit_name', $sale_unit[$index])->first();
            if ($unit) {
                $sale_unit_id = $unit->id;
                $quantity     = $unit->operator === '*'
                    ? $quantity * $unit->operation_value
                    : $quantity / $unit->operation_value;
            }
        }

        $product->qty -= $quantity;
        $product->save();

        $warehouseRecord = null;

        if ($product->is_variant) {
            $variant = ProductVariant::select('id', 'variant_id', 'qty')
                ->FindExactProductWithCode($product_id, $product_code[$index])
                ->first();
            if ($variant) {
                $variant->qty -= $quantity;
                $variant->save();
                $warehouseRecord = Product_Warehouse::FindProductWithVariant(
                    $product_id,
                    $variant->variant_id,
                    $warehouse_id
                )->first();
            }
        } elseif (!empty($product_batch_id[$index])) {
            $batch = ProductBatch::find($product_batch_id[$index]);
            if ($batch) {
                $batch->qty -= $quantity;
                $batch->save();
            }
            $warehouseRecord = Product_Warehouse::where([
                ['product_batch_id', $product_batch_id[$index]],
                ['warehouse_id', $warehouse_id],
            ])->first();
        } else {
            $warehouseRecord = Product_Warehouse::FindProductWithoutVariant($product_id, $warehouse_id)->first();
        }

        if ($warehouseRecord) {
            $warehouseRecord->qty -= $quantity;
            if (!empty($imei_number[$index]) && !str_contains($imei_number[$index], 'null')) {
                $toRemove = explode(',', $imei_number[$index]);
                $existing = explode(',', $warehouseRecord->imei_number ?? '');
                foreach ($toRemove as $imei) {
                    $pos = array_search(trim($imei), $existing);
                    if ($pos !== false) unset($existing[$pos]);
                }
                $warehouseRecord->imei_number = implode(',', array_filter($existing));
            }
            $warehouseRecord->save();
        }

        ProductExchange::create([
            'exchange_id'    => $exchange_id,
            'product_id'     => $product_id,
            'qty'            => $qty[$index],
            'sale_unit_id'   => $sale_unit_id,
            'net_unit_price' => $net_unit_price[$index],
            'discount'       => $discount[$index],
            'tax_rate'       => $tax_rate[$index],
            'tax'            => $tax[$index],
            'total'          => $total[$index],
            'type'           => 'new',
        ]);
    }

    private function processReturnProduct(
        $product_id,
        $index,
        $exchange_id,
        $warehouse_id,
        $qty,
        $sale_unit,
        $net_unit_price,
        $discount,
        $tax_rate,
        $tax,
        $total,
        $product_code,
        $product_batch_id,
        $imei_number,
        $original_product_sale = null
    ) {
        $product      = Product::findOrFail($product_id);
        $sale_unit_id = 0;
        $quantity     = (float) $qty[$index];

        if (!empty($sale_unit[$index]) && $sale_unit[$index] !== 'n/a') {
            $unit = Unit::where('unit_name', $sale_unit[$index])->first();
            if ($unit) {
                $sale_unit_id = $unit->id;
                $quantity     = $unit->operator === '*'
                    ? $quantity * $unit->operation_value
                    : $quantity / $unit->operation_value;
            }
        }

        $product->qty += $quantity;
        $product->save();

        $variant_id      = $original_product_sale->variant_id ?? null;
        $batch_id        = $product_batch_id[$index] ?? ($original_product_sale->product_batch_id ?? null);
        $warehouseRecord = null;

        if ($product->is_variant && $variant_id) {
            $variant = ProductVariant::find($variant_id);
            if ($variant) {
                $variant->qty += $quantity;
                $variant->save();
            }
            $warehouseRecord = Product_Warehouse::FindProductWithVariant(
                $product_id,
                $variant_id,
                $warehouse_id
            )->first();
        } elseif ($batch_id) {
            $batch = ProductBatch::find($batch_id);
            if ($batch) {
                $batch->qty += $quantity;
                $batch->save();
            }
            $warehouseRecord = Product_Warehouse::where([
                ['product_batch_id', $batch_id],
                ['warehouse_id', $warehouse_id],
            ])->first();
        } else {
            $warehouseRecord = Product_Warehouse::FindProductWithoutVariant($product_id, $warehouse_id)->first();
        }

        if ($warehouseRecord) {
            $warehouseRecord->qty += $quantity;
            if (!empty($imei_number[$index]) && !str_contains($imei_number[$index], 'null')) {
                $warehouseRecord->imei_number = $warehouseRecord->imei_number
                    ? $warehouseRecord->imei_number . ',' . $imei_number[$index]
                    : $imei_number[$index];
            }
            $warehouseRecord->save();
        }

        ProductExchange::create([
            'exchange_id'    => $exchange_id,
            'product_id'     => $product_id,
            'qty'            => $qty[$index],
            'sale_unit_id'   => $sale_unit_id,
            'net_unit_price' => $net_unit_price[$index],
            'discount'       => $discount[$index],
            'tax_rate'       => $tax_rate[$index],
            'tax'            => $tax[$index],
            'total'          => $total[$index],
            'type'           => 'returned',
        ]);
    }

    public function searchByReference(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('exchange-add')) {
            return response()->json(['status' => false, 'message' => __('db.Sorry! You are not allowed')]);
        }
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        $lims_sale_data = Sale::where('reference_no', trim($request->reference))->first();
        if (!$lims_sale_data) {
            return response()->json(['status' => false, 'message' => 'Reference number not found']);
        }
        $lims_product_sale_data = Product_Sale::with(['product', 'unit', 'productBatch'])->where('sale_id', $lims_sale_data->id)->get();
        $general_setting = GeneralSetting::latest()->first();
        $html = view('backend.sale-exchange.partials.sale-products',compact('lims_product_sale_data', 'general_setting', 'lims_sale_data'))->render();

        return response()->json(['status'  => true,'html'    => $html,'sale_id' => $lims_sale_data->id, // BUG FIX #5: send back to JS to update hidden input
        ]);
    }
}
