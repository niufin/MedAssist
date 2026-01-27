<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hospital_admin_id')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete()->after('patient_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hospital_admin_id']);
            $table->dropColumn('hospital_admin_id');
        });
    }
};

