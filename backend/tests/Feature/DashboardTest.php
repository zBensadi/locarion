<?php

namespace Tests\Feature;

use App\Domain\Identity\Models\User;
use App\Domain\Tenancy\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_access_dashboard()
    {
        $superAdmin = User::factory()->create(['agency_id' => null, 'is_active' => true]);
        setPermissionsTeamId('00000000-0000-0000-0000-000000000000');
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('role', 'super-admin');
    }

    public function test_agency_admin_can_access_dashboard()
    {
        $agency = Agency::factory()->create();
        $agencyAdmin = User::factory()->create(['agency_id' => $agency->id, 'is_active' => true]);
        
        setPermissionsTeamId($agency->id);
        $agencyAdmin->assignRole('agency-admin');

        $response = $this->actingAs($agencyAdmin)->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('role', 'agency-admin');
    }
}
