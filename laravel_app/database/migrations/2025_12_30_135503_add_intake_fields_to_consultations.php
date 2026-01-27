<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Track where we are in the chat (asking_name, asking_age, asking_gender, consulting, finished)
            $table->string('status')->default('asking_name'); 
            
            // Store specific patient details
            $table->string('patient_name')->nullable();
            $table->string('patient_age')->nullable();
            $table->string('patient_gender')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['status', 'patient_name', 'patient_age', 'patient_gender']);
        });
    }
};