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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id(); // Laravel equivalent of AUTO_INCREMENT primary key
            $table->unsignedInteger('payment_id');
            $table->unsignedInteger('purchase_id');
            $table->double('allocated_amount');
            $table->softDeletes('deleted_at')->nullable();
            $table->timestamps(); // Creates created_at and updated_at columns 
            // Foreign key constraints
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade');
            $table->foreign('purchase_id')
                ->references('id')
                ->on('purchases')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
