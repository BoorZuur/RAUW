<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\District;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
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
            'hub_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Attach a single canonical department and a single district to every
     * created manager so the one-or-more invariants hold without manual setup.
     * These run first; `withDepartments()`/`forDepartment()` and
     * `withDistricts()` can override the assignments afterwards.
     */
    public function configure(): static
    {
        return $this
            ->afterCreating(function (Manager $manager): void {
                $departmentId = $this->canonicalDepartment(
                    fake()->randomElement(array_keys(self::CANONICAL_DEPARTMENTS))
                )->id;

                $manager->departments()->syncWithoutDetaching([$departmentId]);
            })
            ->afterCreating(function (Manager $manager): void {
                $this->syncDistricts($manager, [District::factory()->create()->id]);
            });
    }

    /**
     * Attach a specific set of districts after creation, replacing any default
     * district assignment. Accepts District models or district IDs.
     *
     * @param  iterable<District|int>  $districts
     */
    public function withDistricts(iterable $districts): static
    {
        $ids = [];

        foreach ($districts as $district) {
            $ids[] = $district instanceof District ? $district->id : $district;
        }

        return $this->afterCreating(function (Manager $manager) use ($ids): void {
            $this->syncDistricts($manager, $ids);
        });
    }

    /**
     * Replace a manager's district pivot rows with the given district IDs.
     *
     * @param  array<int, int>  $districtIds
     */
    private function syncDistricts(Manager $manager, array $districtIds): void
    {
        DB::table('district_manager')->where('manager_id', $manager->id)->delete();

        $rows = [];

        foreach (array_unique($districtIds) as $districtId) {
            $rows[] = [
                'manager_id' => $manager->id,
                'district_id' => $districtId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('district_manager')->insertOrIgnore($rows);
        }
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
