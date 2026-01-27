<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prescription_caches', function (Blueprint $table) {
            $table->id();
            $table->string('signature_hash', 64)->unique();
            $table->json('signature_payload');
            $table->string('model')->nullable();
            $table->text('ai_analysis')->nullable();
            $table->json('prescription_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prescription_caches');
    }
};

