<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('name');
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete()->after('brand_name');
            $table->foreignId('dosage_form_id')->nullable()->constrained('dosage_forms')->nullOnDelete()->after('type');
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete()->after('dosage_form_id');
            $table->string('schedule')->nullable()->after('therapeutic_class');
            $table->boolean('rx_required')->default(false)->after('schedule');
            $table->json('generic_display')->nullable()->after('rx_required');
            $table->index(['brand_name']);
            $table->index(['schedule']);
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex(['brand_name']);
            $table->dropIndex(['schedule']);
            $table->dropColumn(['brand_name','manufacturer_id','dosage_form_id','route_id','schedule','rx_required','generic_display']);
        });
    }
};

