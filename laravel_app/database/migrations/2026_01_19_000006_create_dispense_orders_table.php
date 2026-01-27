<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispense_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_store_id')->constrained('pharmacy_stores')->onDelete('cascade');
            $table->foreignId('consultation_id')->constrained('consultations')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('doctor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pharmacist_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('open');
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['pharmacy_store_id', 'consultation_id']);
            $table->index(['pharmacy_store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispense_orders');
    }
};

