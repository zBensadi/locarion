<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'status' => 'active',
        ];
    }
}
