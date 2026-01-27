<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->unsignedBigInteger('source_product_id')->nullable()->after('id');
            $table->string('source_name')->nullable()->after('source_product_id');
            $table->boolean('is_discontinued')->default(false)->after('is_active');
            $table->unsignedSmallInteger('num_active_ingredients')->nullable()->after('is_discontinued');
            $table->string('primary_ingredient')->nullable()->after('num_active_ingredients');
            $table->string('primary_strength')->nullable()->after('primary_ingredient');
            $table->string('manufacturer_raw')->nullable()->after('manufacturer_id');

            $table->index(['source_product_id']);
            $table->index(['is_discontinued']);
            $table->index(['primary_ingredient']);
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex(['source_product_id']);
            $table->dropIndex(['is_discontinued']);
            $table->dropIndex(['primary_ingredient']);
            $table->dropColumn([
                'source_product_id',
                'source_name',
                'is_discontinued',
                'num_active_ingredients',
                'primary_ingredient',
                'primary_strength',
                'manufacturer_raw',
            ]);
        });
    }
};

