<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SafeTest extends Command
{
    protected $signature = 'safe:test {--restore-mysql : Run db:restore-mysql after tests} {--csv-path= : CSV path forwarded to db:restore-mysql} {--super-admin-password= : Password forwarded to db:restore-mysql when needed}';
    protected $description = 'Run tests safely on SQLite; optionally restore MySQL data after';

    public function handle(): int
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->error('SQLite driver is not available (pdo_sqlite extension missing).');
            $this->error('Enable pdo_sqlite/sqlite3 in php.ini, or use a dedicated MySQL testing database instead of production.');
            return 1;
        }

        $artisan = PHP_BINARY . ' artisan';

        $env = array_merge($_ENV, $_SERVER, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => base_path('database/database.sqlite'),
            'DB_FOREIGN_KEYS' => 'true',
        ]);

        $sqlitePath = (string) ($env['DB_DATABASE'] ?? '');
        if ($sqlitePath !== '' && !file_exists($sqlitePath)) {
            @mkdir(dirname($sqlitePath), 0777, true);
            @touch($sqlitePath);
        }

        $prep = Process::fromShellCommandline($artisan . ' migrate:fresh --force', base_path(), $env, null, null);
        $prep->setTty(false);
        $prep->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        if (!$prep->isSuccessful()) {
            return $prep->getExitCode() ?? 1;
        }

        $proc = Process::fromShellCommandline($artisan . ' test', base_path(), $env, null, null);
        $proc->setTty(false);
        $proc->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$proc->isSuccessful()) {
            return $proc->getExitCode() ?? 1;
        }

        if (!(bool) $this->option('restore-mysql')) {
            return 0;
        }

        $args = ['db:restore-mysql'];
        if ($this->option('csv-path')) {
            $args['--csv-path'] = (string) $this->option('csv-path');
        }
        if ($this->option('super-admin-password')) {
            $args['--super-admin-password'] = (string) $this->option('super-admin-password');
        }

        $exit = $this->call('db:restore-mysql', $args);
        return $exit;
    }
}
