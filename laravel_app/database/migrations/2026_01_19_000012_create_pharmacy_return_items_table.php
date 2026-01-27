<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_return_id')->constrained('pharmacy_returns')->onDelete('cascade');
            $table->foreignId('pharmacy_invoice_item_id')->constrained('pharmacy_invoice_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_return_items');
    }
};

