<?php

namespace Database\Factories;

use App\Enums\Department;
use App\Enums\ReportPeriod;
use App\Models\Manager;
use App\Models\ReportSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSnapshot>
 */
class ReportSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->unique()->dateTimeBetween('-10 years', 'now')->format('Y-m-d');

        return [
            'generated_by_manager_id' => Manager::factory(),
            'period' => fake()->randomElement(ReportPeriod::cases()),
            'period_start' => $periodStart,
            'period_end' => date('Y-m-d', strtotime($periodStart.' +6 days')),
            'total_issues' => fake()->numberBetween(0, 100),
            'open_issues' => fake()->numberBetween(0, 50),
            'resolved_issues' => fake()->numberBetween(0, 50),
            'avg_resolution_days' => fake()->randomFloat(2, 0.5, 21),
            'metrics' => [
                'priority_counts' => [
                    10 => fake()->numberBetween(0, 30),
                    20 => fake()->numberBetween(0, 30),
                    30 => fake()->numberBetween(0, 30),
                ],
                'department_counts' => [
                    Department::DistrictManagement->value => fake()->numberBetween(0, 50),
                    Department::BoaYouth->value => fake()->numberBetween(0, 50),
                ],
            ],
            'satisfaction_rate' => fake()->randomFloat(2, 0, 100),
            'generated_at' => now(),
        ];
    }
}
