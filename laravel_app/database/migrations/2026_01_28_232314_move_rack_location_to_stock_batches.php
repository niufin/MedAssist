<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('rack_location');
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->string('rack_location')->nullable()->after('sale_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropColumn('rack_location');
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->string('rack_location')->nullable();
        });
    }
};
