<?php

namespace Tests\Feature;

use App\Domain\Identity\Models\User;
use App\Domain\Tenancy\Models\Agency;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityAndTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_allows_a_user_to_log_in_and_sets_correct_tenant_context()
    {
        $agency = Agency::create(['name' => 'Test Agency', 'slug' => 'test-agency', 'status' => 'active']);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'agency_id' => $agency->id,
            'is_active' => true,
        ]);

        setPermissionsTeamId($agency->id);
        $user->assignRole('agency-admin');

        $response = $this->withSession([])
            ->withHeaders(['Referer' => 'http://localhost'])
            ->postJson('/api/v1/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'admin@test.com');

        $meResponse = $this->actingAs($user)->getJson('/api/v1/me');
        $meResponse->assertStatus(200);
        $this->assertTrue($user->hasRole('agency-admin'));
    }

    public function test_prevents_inactive_users_from_authenticating()
    {
        $agency = Agency::create(['name' => 'Test Agency', 'slug' => 'test-agency-2', 'status' => 'active']);

        $user = User::create([
            'name' => 'Inactive',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'agency_id' => $agency->id,
            'is_active' => false,
        ]);

        $response = $this->withSession([])
            ->withHeaders(['Referer' => 'http://localhost'])
            ->postJson('/api/v1/login', [
                'email' => 'inactive@test.com',
                'password' => 'password',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_enforces_tenant_isolation_via_global_scope()
    {
        $agency1 = Agency::create(['name' => 'Agency 1', 'slug' => 'a1', 'status' => 'active']);

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'u1@test.com',
            'password' => Hash::make('password'),
            'agency_id' => $agency1->id,
            'is_active' => true,
        ]);

        $this->actingAs($user1)->getJson('/api/v1/me');

        $tenantContext = app(\App\Domain\Tenancy\Services\TenantContext::class);
        $this->assertEquals($agency1->id, $tenantContext->getAgencyId());
    }
}
