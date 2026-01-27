<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuperAdminDeletionProtectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_cannot_delete_own_account_from_profile(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $resp = $this->actingAs($superAdmin)->delete('/profile', [
            'password' => 'password',
        ]);

        $resp->assertRedirect('/profile');
        $resp->assertSessionHas('error');
        $this->assertNotNull(User::find($superAdmin->id));
    }

    public function test_super_admin_cannot_use_connect_visits(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $resp = $this->actingAs($superAdmin)->post('/patient/connect-visits', [
            'mrn' => '1234567890',
        ]);

        $resp->assertForbidden();
        $this->assertNotNull(User::find($superAdmin->id));
    }
}

