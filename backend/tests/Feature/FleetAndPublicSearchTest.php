<?php

namespace Tests\Feature;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Identity\Models\User;
use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\Tenancy\Models\Agency;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetAndPublicSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_manage_vehicle_categories(): void
    {
        $superAdmin = User::factory()->create();
        setPermissionsTeamId('00000000-0000-0000-0000-000000000000');
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin);

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'SUV',
            'description' => 'Sport Utility Vehicle',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('vehicle_categories', ['name' => 'SUV']);
    }

    public function test_agency_admin_can_manage_vehicles_within_tenant(): void
    {
        $agency = Agency::factory()->create(['status' => 'active']);
        $category = VehicleCategory::factory()->create(['name' => 'SUV']);

        $admin = User::factory()->create(['agency_id' => $agency->id]);
        setPermissionsTeamId($agency->id);
        $admin->assignRole('agency-admin');

        $this->actingAs($admin);

        $response = $this->postJson('/api/v1/vehicles', [
            'category_id' => $category->id,
            'make' => 'Toyota',
            'model' => 'RAV4',
            'year' => 2023,
            'license_plate' => 'ABC-1234',
            'daily_rate' => 5000,
            'status' => 'available',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('vehicles', [
            'agency_id' => $agency->id,
            'license_plate' => 'ABC-1234',
        ]);
    }

    public function test_public_can_search_available_vehicles_from_active_agencies(): void
    {
        $activeAgency = Agency::factory()->create(['status' => 'active']);
        $inactiveAgency = Agency::factory()->create(['status' => 'inactive']);
        $category = VehicleCategory::factory()->create();

        // Available vehicle in active agency
        $availableActive = Vehicle::factory()->create([
            'agency_id' => $activeAgency->id,
            'category_id' => $category->id,
            'status' => 'available',
        ]);

        // Reserved vehicle in active agency
        $reservedActive = Vehicle::factory()->create([
            'agency_id' => $activeAgency->id,
            'category_id' => $category->id,
            'status' => 'reserved',
        ]);

        // Available vehicle in inactive agency
        $availableInactive = Vehicle::factory()->create([
            'agency_id' => $inactiveAgency->id,
            'category_id' => $category->id,
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/v1/public/vehicles');

        $response->assertOk();

        // Should only return the available vehicle from active agency
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $availableActive->id);
    }
}
