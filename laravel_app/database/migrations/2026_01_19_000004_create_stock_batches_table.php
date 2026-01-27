<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_store_id')->constrained('pharmacy_stores')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->timestamps();

            $table->index(['pharmacy_store_id', 'medicine_id']);
            $table->index(['expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};

