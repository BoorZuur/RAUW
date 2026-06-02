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
     * actor department migration. Managers belong to one or more of these.
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
            'district_id' => District::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Attach a single canonical department to every created manager so the
     * one-or-more department invariant holds without manual setup. This runs
     * first; `withDepartments()`/`forDepartment()` can override the
     * assignment afterwards.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Manager $manager): void {
            $departmentId = $this->canonicalDepartment(
                fake()->randomElement(array_keys(self::CANONICAL_DEPARTMENTS))
            )->id;

            $manager->departments()->syncWithoutDetaching([$departmentId]);
        });
    }

    /**
     * Assign the manager to the Wijkbeheer department.
     */
    public function wijkbeheer(): static
    {
        return $this->afterCreating(function (Manager $manager): void {
            $manager->departments()->sync([$this->canonicalDepartment('wijkbeheer')->id]);
        });
    }

    /**
     * Assign the manager to the BOA / Jeugd department.
     */
    public function boaJeugd(): static
    {
        return $this->afterCreating(function (Manager $manager): void {
            $manager->departments()->sync([$this->canonicalDepartment('boa_jeugd')->id]);
        });
    }

    /**
     * Assign the manager to a specific department, replacing the default
     * canonical assignment.
     */
    public function forDepartment(Department $department): static
    {
        return $this->afterCreating(function (Manager $manager) use ($department): void {
            $manager->departments()->sync([$department->id]);
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

        return $this->afterCreating(function (Manager $manager) use ($ids): void {
            $manager->departments()->sync($ids);
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
