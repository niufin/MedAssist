<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_fulfillments', function (Blueprint $table) {
            $table->unique(['consultation_id', 'medicine_name'], 'prescription_fulfillments_consultation_medicine_unique');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_fulfillments', function (Blueprint $table) {
            $table->dropUnique('prescription_fulfillments_consultation_medicine_unique');
        });
    }
};

