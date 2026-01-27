<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'clinic_address')) {
                $table->string('clinic_address', 500)->nullable()->after('contact_number');
            }
            if (!Schema::hasColumn('users', 'clinic_contact_number')) {
                $table->string('clinic_contact_number', 50)->nullable()->after('clinic_address');
            }
            if (!Schema::hasColumn('users', 'clinic_email')) {
                $table->string('clinic_email')->nullable()->after('clinic_contact_number');
            }
            if (!Schema::hasColumn('users', 'clinic_registration_number')) {
                $table->string('clinic_registration_number')->nullable()->after('clinic_email');
            }
            if (!Schema::hasColumn('users', 'clinic_gstin')) {
                $table->string('clinic_gstin')->nullable()->after('clinic_registration_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'clinic_gstin')) {
                $table->dropColumn('clinic_gstin');
            }
            if (Schema::hasColumn('users', 'clinic_registration_number')) {
                $table->dropColumn('clinic_registration_number');
            }
            if (Schema::hasColumn('users', 'clinic_email')) {
                $table->dropColumn('clinic_email');
            }
            if (Schema::hasColumn('users', 'clinic_contact_number')) {
                $table->dropColumn('clinic_contact_number');
            }
            if (Schema::hasColumn('users', 'clinic_address')) {
                $table->dropColumn('clinic_address');
            }
        });
    }
};

