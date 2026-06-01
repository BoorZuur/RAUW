<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Officer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Officer>
 */
class OfficerFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'badge_number' => fake()->unique()->bothify('BOA-####'),
            'district_id' => District::factory(),
            'is_active' => true,
        ];
    }
}
