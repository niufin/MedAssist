<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispense_order_id')->constrained('dispense_orders')->onDelete('cascade');
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->onDelete('set null');
            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('instruction')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('dispensed_quantity')->default(0);
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->onDelete('set null');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['dispense_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispense_items');
    }
};

