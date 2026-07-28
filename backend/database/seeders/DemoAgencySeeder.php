<?php

namespace Database\Seeders;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Identity\Models\User;
use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\Tenancy\Models\Agency;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAgencySeeder extends Seeder
{
    public function run(): void
    {
        // Platform Categories
        $suv = VehicleCategory::firstOrCreate(['name' => 'SUV'], ['description' => 'Sport Utility Vehicle']);
        $sedan = VehicleCategory::firstOrCreate(['name' => 'Sedan'], ['description' => 'Standard Sedan']);
        $compact = VehicleCategory::firstOrCreate(['name' => 'Compact'], ['description' => 'Compact Car']);
        $pickup = VehicleCategory::firstOrCreate(['name' => 'Pickup'], ['description' => 'Pickup Truck']);
        $categories = [$suv, $sedan, $compact, $pickup];

        $models = [
            ['make' => 'Toyota', 'model' => 'Corolla', 'category_id' => $sedan->id, 'rate' => 3500],
            ['make' => 'Hyundai', 'model' => 'i10', 'category_id' => $compact->id, 'rate' => 2500],
            ['make' => 'Renault', 'model' => 'Clio', 'category_id' => $compact->id, 'rate' => 2800],
            ['make' => 'Peugeot', 'model' => '208', 'category_id' => $compact->id, 'rate' => 2900],
            ['make' => 'Volkswagen', 'model' => 'Golf', 'category_id' => $compact->id, 'rate' => 4000],
            ['make' => 'Dacia', 'model' => 'Logan', 'category_id' => $sedan->id, 'rate' => 2400],
            ['make' => 'Kia', 'model' => 'Picanto', 'category_id' => $compact->id, 'rate' => 2300],
            ['make' => 'Nissan', 'model' => 'Micra', 'category_id' => $compact->id, 'rate' => 2700],
            ['make' => 'Hyundai', 'model' => 'Tucson', 'category_id' => $suv->id, 'rate' => 6000],
            ['make' => 'Toyota', 'model' => 'Hilux', 'category_id' => $pickup->id, 'rate' => 7500],
        ];

        // 1. Create 5 Agencies (4 active, 1 inactive)
        $agencies = [];
        for ($i = 1; $i <= 5; $i++) {
            $status = $i === 5 ? 'suspended' : 'active';
            $agency = Agency::create([
                'name' => "Locarion Agency {$i}",
                'slug' => 'agency-' . $i . '-' . Str::random(5),
                'status' => $status,
            ]);
            $agencies[] = $agency;

            // Create Agency Admin
            $agencyAdmin = User::create([
                'name' => "Admin Agency {$i}",
                'email' => "admin{$i}@locarion.com",
                'password' => Hash::make('password'),
                'agency_id' => $agency->id,
                'is_active' => true,
            ]);
            setPermissionsTeamId($agency->id);
            $agencyAdmin->assignRole('agency-admin');

            // Create 2 Employees
            for ($j = 1; $j <= 2; $j++) {
                $employee = User::create([
                    'name' => "Employee {$j} Agency {$i}",
                    'email' => "employee{$j}_{$i}@locarion.com",
                    'password' => Hash::make('password'),
                    'agency_id' => $agency->id,
                    'is_active' => true,
                ]);
                $employee->assignRole('employee');
                $employee->givePermissionTo(['fleet.view', 'reservations.view']);
            }
        }

        // Demo Agency (to match demo credentials perfectly)
        // Let's modify Agency 1 to be the primary demo agency
        $demoAgency = $agencies[0];
        $demoAgency->update(['name' => 'Demo Agency', 'slug' => 'demo-agency']);
        $demoAdmin = User::where('email', 'admin1@locarion.com')->first();
        $demoAdmin->update(['email' => 'admin@demo-agency.com', 'name' => 'Demo Admin']);
        $demoEmployee = User::where('email', 'employee1_1@locarion.com')->first();
        $demoEmployee->update(['email' => 'employee@demo-agency.com', 'name' => 'Demo Employee']);

        // 2. Create exactly 60 vehicles
        // Statuses: 40 Available, 10 Reserved, 6 Maintenance, 4 Retired
        $vehicleStatuses = array_merge(
            array_fill(0, 40, 'available'),
            array_fill(0, 10, 'reserved'),
            array_fill(0, 6, 'maintenance'),
            array_fill(0, 4, 'retired')
        );
        shuffle($vehicleStatuses);

        $vehicles = [];
        foreach ($vehicleStatuses as $index => $status) {
            $model = $models[array_rand($models)];
            $agency = $agencies[array_rand($agencies)];

            $vehicle = Vehicle::factory()->create([
                'agency_id' => $agency->id,
                'category_id' => $model['category_id'],
                'make' => $model['make'],
                'model' => $model['model'],
                'year' => rand(2018, 2024),
                'daily_rate' => $model['rate'],
                'status' => $status,
                'license_plate' => strtoupper(Str::random(3)) . '-' . rand(1000, 9999),
            ]);
            $vehicles[] = $vehicle;
        }

        // 3. Create exactly 100 reservations
        // Statuses: 20 Pending, 35 Confirmed, 35 Completed, 10 Cancelled
        $reservationStatuses = array_merge(
            array_fill(0, 20, 'pending'),
            array_fill(0, 35, 'confirmed'),
            array_fill(0, 35, 'completed'),
            array_fill(0, 10, 'cancelled')
        );
        shuffle($reservationStatuses);

        foreach ($reservationStatuses as $status) {
            $vehicle = $vehicles[array_rand($vehicles)];

            // Adjust dates based on status
            if ($status === 'completed') {
                $start = Carbon::now()->subDays(rand(10, 30));
                $end = (clone $start)->addDays(rand(2, 7));
            } elseif ($status === 'confirmed' || $status === 'pending') {
                $start = Carbon::now()->addDays(rand(1, 14));
                $end = (clone $start)->addDays(rand(2, 7));
            } else { // cancelled
                $start = Carbon::now()->addDays(rand(1, 14));
                $end = (clone $start)->addDays(rand(2, 7));
            }

            $days = max(1, $start->diffInDays($end));
            $totalPrice = $vehicle->daily_rate * $days;

            Reservation::factory()->create([
                'agency_id' => $vehicle->agency_id,
                'vehicle_id' => $vehicle->id,
                'customer_name' => fake()->name(),
                'customer_email' => fake()->safeEmail(),
                'customer_phone' => fake()->phoneNumber(),
                'start_date' => $start,
                'end_date' => $end,
                'total_price' => $totalPrice,
                'status' => $status,
            ]);
        }
    }
}
