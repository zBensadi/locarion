<?php

namespace Tests\Feature;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Identity\Models\User;
use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\Tenancy\Models\Agency;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_public_can_create_reservation_and_pricing_is_calculated(): void
    {
        $agency = Agency::factory()->create(['status' => 'active']);
        $category = VehicleCategory::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'category_id' => $category->id,
            'status' => 'available',
            'daily_rate' => 5000, // 50.00
        ]);

        $response = $this->postJson('/api/v1/public/reservations', [
            'vehicle_id' => $vehicle->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+1234567890',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reservations', [
            'vehicle_id' => $vehicle->id,
            'agency_id' => $agency->id,
            'customer_name' => 'John Doe',
            'daily_rate_snapshot' => 5000,
            'total_price' => 15000, // 3 days * 5000
            'status' => 'pending',
        ]);
    }

    public function test_vehicle_availability_prevents_overlapping_bookings(): void
    {
        $agency = Agency::factory()->create(['status' => 'active']);
        $category = VehicleCategory::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'category_id' => $category->id,
            'status' => 'available',
        ]);

        // Existing reservation from Day 3 to Day 5
        Reservation::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'confirmed',
        ]);

        // Attempt overlapping reservation: Day 2 to Day 4 (overlaps on Day 3-4)
        $response = $this->postJson('/api/v1/public/reservations', [
            'vehicle_id' => $vehicle->id,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date' => now()->addDays(4)->format('Y-m-d'),
        ]);

        $response->assertJsonValidationErrors('vehicle_id');
    }

    public function test_agency_admin_can_update_reservation_status(): void
    {
        $agency = Agency::factory()->create(['status' => 'active']);

        $admin = User::factory()->create(['agency_id' => $agency->id]);
        setPermissionsTeamId($agency->id);
        $admin->assignRole('agency-admin');

        $category = VehicleCategory::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'category_id' => $category->id,
        ]);

        $reservation = Reservation::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->putJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertOk();
        $this->assertEquals('confirmed', $reservation->fresh()->status);
    }
}
