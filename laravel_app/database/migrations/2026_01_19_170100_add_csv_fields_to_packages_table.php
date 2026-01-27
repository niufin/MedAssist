<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('price_inr', 12, 2)->nullable()->after('mrp');
            $table->string('packaging_raw')->nullable()->after('barcode');
            $table->index(['price_inr']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['price_inr']);
            $table->dropColumn(['price_inr', 'packaging_raw']);
        });
    }
};

