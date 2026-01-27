<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispense_orders', function (Blueprint $table) {
            $table->dropUnique('dispense_orders_pharmacy_store_id_consultation_id_unique');
            $table->index(['pharmacy_store_id', 'consultation_id'], 'dispense_orders_store_consultation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dispense_orders', function (Blueprint $table) {
            $table->dropIndex('dispense_orders_store_consultation_idx');
            $table->unique(['pharmacy_store_id', 'consultation_id'], 'dispense_orders_pharmacy_store_id_consultation_id_unique');
        });
    }
};
