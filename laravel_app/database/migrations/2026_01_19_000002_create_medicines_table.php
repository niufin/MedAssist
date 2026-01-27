<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('strength')->nullable();
            $table->string('type')->nullable();
            $table->string('therapeutic_class')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'strength']);
            $table->index(['name', 'strength']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};

