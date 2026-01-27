<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ScrapeMedicines::class,
        \App\Console\Commands\ImportMedicinesCsv::class,
        \App\Console\Commands\RestoreMysqlData::class,
        \App\Console\Commands\SafeTest::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // no scheduled tasks
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
