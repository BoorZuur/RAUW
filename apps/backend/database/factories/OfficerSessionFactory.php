<?php

namespace Database\Factories;

use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficerSession>
 */
class OfficerSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'officer_id' => Officer::factory(),
            'shift_start' => now()->subHours(2),
            'shift_end' => null,
            'start_lat' => fake()->latitude(51.8, 52.5),
            'start_lng' => fake()->longitude(4.0, 5.5),
            'last_lat' => fake()->latitude(51.8, 52.5),
            'last_lng' => fake()->longitude(4.0, 5.5),
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }
}
