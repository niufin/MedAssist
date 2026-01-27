<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class RestoreMysqlData extends Command
{
    protected $signature = 'db:restore-mysql
        {--csv-path= : Absolute CSV path for medicines import}
        {--super-admin-email=sultan@niufin.cloud : Super admin email}
        {--super-admin-password= : Super admin password (required if user does not exist)}
        {--skip-medicines : Skip medicines import}';

    protected $description = 'Restore critical MySQL data (super admin + medicines import) safely';

    public function handle(): int
    {
        if ((string) config('app.env') === 'testing') {
            $this->error('Refusing to run in testing environment.');
            return 1;
        }

        $driver = (string) config('database.default');
        if ($driver === 'sqlite') {
            $this->error('Refusing to run on sqlite. Run this using your MySQL .env configuration.');
            return 1;
        }

        $email = trim((string) $this->option('super-admin-email'));
        $password = (string) $this->option('super-admin-password');
        if ($email === '') {
            $this->error('Provide --super-admin-email');
            return 1;
        }

        $existing = User::where('email', $email)->first();
        if (!$existing && $password === '') {
            $this->error('Super admin user does not exist. Provide --super-admin-password to create it.');
            return 1;
        }

        $attrs = [
            'name' => $existing?->name ?: 'Sultan',
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ];
        if ($existing && $password !== '') {
            $attrs['password'] = Hash::make($password);
        }
        if (!$existing) {
            $attrs['password'] = Hash::make($password);
            $attrs['email_verified_at'] = now();
        }

        $user = User::updateOrCreate(['email' => $email], $attrs);
        $this->info("Super admin ensured: user_id={$user->id} email={$user->email}");

        if (!(bool) $this->option('skip-medicines')) {
            $csv = trim((string) $this->option('csv-path'));
            if ($csv === '') {
                $csv = 'C:\\Users\\Sultan\\Downloads\\indian_pharmaceutical_products_clean.csv';
            }
            if (!file_exists($csv)) {
                $this->error("CSV file not found: {$csv}");
                return 1;
            }

            $output = 'storage/catalog/csv_import.json';
            $anom = 'storage/catalog/anomalies.csv';

            $exit = Artisan::call('pharmacy:import-csv', [
                '--path' => $csv,
                '--output' => $output,
                '--anomalies' => $anom,
            ]);

            $this->output->write(Artisan::output());

            if ($exit !== 0) {
                $this->error('Medicines import failed.');
                return $exit;
            }
        }

        return 0;
    }
}
