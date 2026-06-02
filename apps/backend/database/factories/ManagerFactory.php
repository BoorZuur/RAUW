<?php

namespace Database\Factories;

use App\Models\Department;
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
     * Canonical department rows actors are assigned to, mirroring the
     * actor department migration. Managers belong to exactly one of these.
     *
     * @var array<string, string>
     */
    private const CANONICAL_DEPARTMENTS = [
        'wijkbeheer' => 'Wijkbeheer',
        'boa_jeugd' => 'BOA / Jeugd',
    ];

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
            'department_id' => fn () => $this->canonicalDepartment(
                fake()->randomElement(array_keys(self::CANONICAL_DEPARTMENTS))
            )->id,
            'district_id' => District::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Assign the manager to the Wijkbeheer department.
     */
    public function wijkbeheer(): static
    {
        return $this->state(fn () => [
            'department_id' => $this->canonicalDepartment('wijkbeheer')->id,
        ]);
    }

    /**
     * Assign the manager to the BOA / Jeugd department.
     */
    public function boaJeugd(): static
    {
        return $this->state(fn () => [
            'department_id' => $this->canonicalDepartment('boa_jeugd')->id,
        ]);
    }

    /**
     * Assign the manager to a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn () => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Create or reuse a canonical department row by code.
     */
    private function canonicalDepartment(string $code): Department
    {
        return Department::firstOrCreate(
            ['code' => $code],
            ['name' => self::CANONICAL_DEPARTMENTS[$code], 'is_active' => true],
        );
    }
}
