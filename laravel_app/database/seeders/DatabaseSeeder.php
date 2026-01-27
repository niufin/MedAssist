<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::where('email', 'sultan@niufin.cloud')->first();
        if (!$user) {
            User::factory()->create([
                'name' => 'Sultan',
                'email' => 'sultan@niufin.cloud',
                'role' => User::ROLE_SUPER_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'password' => bcrypt('password'),
            ]);
        } else {
            $user->update([
                'role' => User::ROLE_SUPER_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ]);
        }

    }
}
