<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'postal_prefix' => fake()->unique()->numberBetween(1000, 9999),
            'center_lat' => fake()->latitude(51.8, 52.5),
            'center_lng' => fake()->longitude(4.0, 5.5),
            'radius_meters' => fake()->numberBetween(750, 3500),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}
