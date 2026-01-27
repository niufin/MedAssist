<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_invoice_id')->constrained('pharmacy_invoices')->onDelete('cascade');
            $table->decimal('refund_total', 12, 2)->default(0);
            $table->string('status')->default('processed');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['pharmacy_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_returns');
    }
};

