<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('consultations', function (Blueprint $table) {
        $table->id();
        $table->text('symptoms');
        $table->text('ai_analysis')->nullable();
        $table->json('ai_sources')->nullable();
        $table->text('doctor_notes')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
