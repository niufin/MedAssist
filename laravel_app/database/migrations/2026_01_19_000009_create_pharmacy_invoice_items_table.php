<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_invoice_id')->constrained('pharmacy_invoices')->onDelete('cascade');
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->onDelete('set null');
            $table->string('medicine_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->onDelete('set null');
            $table->timestamps();

            $table->index(['pharmacy_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_invoice_items');
    }
};

