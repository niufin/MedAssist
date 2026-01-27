<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE medicines ADD FULLTEXT ft_medicines_name (name)');
            DB::statement('ALTER TABLE medicines ADD FULLTEXT ft_medicines_generic (generic_display)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE medicines DROP INDEX ft_medicines_name');
            DB::statement('ALTER TABLE medicines DROP INDEX ft_medicines_generic');
        }
    }
};

