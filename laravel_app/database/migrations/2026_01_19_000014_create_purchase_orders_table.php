<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_store_id')->constrained('pharmacy_stores')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('po_no')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('ordered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pharmacy_store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};

