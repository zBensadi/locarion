<?php

namespace Database\Factories;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\Tenancy\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'category_id' => VehicleCategory::factory(),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Audi']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2018, date('Y')),
            'license_plate' => fake()->unique()->bothify('???-####'),
            'daily_rate' => fake()->numberBetween(3000, 15000), // in cents
            'status' => 'available',
        ];
    }
}
