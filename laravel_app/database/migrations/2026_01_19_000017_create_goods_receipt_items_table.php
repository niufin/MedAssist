<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->onDelete('set null');
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->onDelete('set null');
            $table->string('medicine_name');
            $table->integer('quantity_received');
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};

