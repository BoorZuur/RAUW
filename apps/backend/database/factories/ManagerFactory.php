<?php

namespace Database\Factories;

use App\Enums\Department;
use App\Models\District;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Manager>
 */
class ManagerFactory extends Factory
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
            'department' => fake()->randomElement(Department::cases()),
            'district_id' => District::factory(),
            'is_active' => true,
        ];
    }
}
