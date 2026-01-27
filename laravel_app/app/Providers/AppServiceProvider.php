<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            Config::set('database.default', 'sqlite');
            $dbPath = database_path('database.sqlite');
            if (!file_exists($dbPath)) {
                @touch($dbPath);
            }
        }

        if ($this->app->runningInConsole()) {
            Event::listen(CommandStarting::class, function (CommandStarting $event) {
                $blocked = [
                    'db:wipe',
                    'migrate:fresh',
                    'migrate:refresh',
                    'migrate:reset',
                    'schema:drop',
                ];

                $cmd = (string) ($event->command ?? '');
                if (!in_array($cmd, $blocked, true)) {
                    return;
                }

                if (config('app.env') === 'testing') {
                    return;
                }

                $driver = (string) config('database.default');
                if ($driver === 'sqlite') {
                    return;
                }

                if ((bool) env('ALLOW_DESTRUCTIVE_DB_COMMANDS', false)) {
                    return;
                }

                Log::warning('db.destructive_command_blocked', [
                    'command' => $cmd,
                    'driver' => $driver,
                    'env' => (string) config('app.env'),
                ]);

                throw new \RuntimeException("Blocked destructive database command '{$cmd}'. Set ALLOW_DESTRUCTIVE_DB_COMMANDS=true to override.");
            });
        }

        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::define('isAdmin', function (User $user) {
            return $user->isAdmin() || $user->isHospitalAdmin();
        });

        Gate::define('isPlatformAdmin', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('isSuperAdmin', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('isPharmacist', function (User $user) {
            return $user->isPharmacist();
        });

        Gate::define('isPharmacyStaff', function (User $user) {
            return $user->isPharmacist() || $user->isHospitalAdmin() || $user->isAdmin();
        });

        Gate::define('isLabAssistant', function (User $user) {
            return $user->isLabAssistant();
        });

        Gate::define('isLabAccess', function (User $user) {
            return $user->isLabAssistant() || $user->isHospitalAdmin() || $user->isAdmin();
        });

        Gate::define('isDoctor', function (User $user) {
            return $user->isDoctor();
        });

        Gate::define('isHospitalAdmin', function (User $user) {
            return $user->isHospitalAdmin();
        });
    }
}
