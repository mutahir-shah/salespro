<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('biller_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('biller_id');
            $table->integer('total_items')->default(0);
            $table->decimal('total_profit', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('calculated_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biller_commissions');
    }
};
