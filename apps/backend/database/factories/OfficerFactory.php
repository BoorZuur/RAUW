<?php

namespace Database\Factories;

use App\Models\Department;
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
     * Canonical department rows actors are assigned to, mirroring the
     * actor department migration. Officers belong to one or more of these.
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
            'badge_number' => fake()->unique()->bothify('BOA-####'),
            'district_id' => District::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Attach a single canonical department to every created officer so the
     * one-or-more department invariant holds without manual setup. This runs
     * first; `withDepartments()` can override the assignment afterwards.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Officer $officer): void {
            $departmentId = $this->canonicalDepartment(
                fake()->randomElement(array_keys(self::CANONICAL_DEPARTMENTS))
            )->id;

            $officer->departments()->syncWithoutDetaching([$departmentId]);
        });
    }

    /**
     * Attach a specific set of departments after creation, replacing the
     * default canonical assignment.
     *
     * @param  iterable<Department|int>  $departments
     */
    public function withDepartments(iterable $departments): static
    {
        $ids = [];

        foreach ($departments as $department) {
            $ids[] = $department instanceof Department ? $department->id : $department;
        }

        return $this->afterCreating(function (Officer $officer) use ($ids): void {
            $officer->departments()->sync($ids);
        });
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
