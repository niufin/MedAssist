<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->decimal('strength_value', 12, 3)->nullable();
            $table->string('strength_unit')->nullable();
            $table->timestamps();

            $table->unique(['medicine_id', 'ingredient_id', 'strength_value', 'strength_unit'], 'uniq_medicine_ingredient_strength');
            $table->index(['ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_ingredients');
    }
};

