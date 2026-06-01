<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueStatusHistory>
 */
class IssueStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'changed_by_officer_id' => null,
            'old_status' => IssueStatus::Open,
            'new_status' => fake()->randomElement(IssueStatus::cases()),
            'officer_lat' => fake()->latitude(51.8, 52.5),
            'officer_lng' => fake()->longitude(4.0, 5.5),
            'note' => fake()->optional()->sentence(),
            'changed_at' => now(),
        ];
    }
}
