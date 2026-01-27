<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_admin_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->default('Pharmacy');
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->unsignedInteger('near_expiry_days')->default(90);
            $table->timestamps();

            $table->unique(['hospital_admin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stores');
    }
};

