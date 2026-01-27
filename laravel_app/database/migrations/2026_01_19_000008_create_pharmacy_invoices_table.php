<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_store_id')->constrained('pharmacy_stores')->onDelete('cascade');
            $table->foreignId('dispense_order_id')->nullable()->constrained('dispense_orders')->onDelete('set null');
            $table->string('invoice_no')->unique();
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['pharmacy_store_id', 'status']);
            $table->index(['dispense_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_invoices');
    }
};

