<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->decimal('pack_size_value', 12, 3)->nullable();
            $table->string('pack_size_unit')->nullable();
            $table->string('pack_type')->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->string('hsn_code')->nullable();
            $table->string('barcode')->nullable();
            $table->timestamps();
            $table->index(['medicine_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};

