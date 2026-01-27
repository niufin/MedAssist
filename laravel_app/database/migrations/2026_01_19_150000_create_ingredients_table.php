<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('synonyms')->nullable();
            $table->string('atc_code')->nullable();
            $table->timestamps();

            $table->unique(['name']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};

