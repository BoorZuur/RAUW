<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficerIssueResolution>
 */
class OfficerIssueResolutionFactory extends Factory
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
            'officer_id' => Officer::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
        ];
    }
}
