<?php

namespace Database\Factories;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Fleet\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $days = fake()->numberBetween(1, 14);
        $end = (clone $start)->modify("+{$days} days");

        $dailyRate = fake()->numberBetween(3000, 15000);

        return [
            'agency_id' => Agency::factory(),
            'vehicle_id' => Vehicle::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'daily_rate_snapshot' => $dailyRate,
            'total_price' => $dailyRate * $days,
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'rejected', 'cancelled']),
        ];
    }
}
