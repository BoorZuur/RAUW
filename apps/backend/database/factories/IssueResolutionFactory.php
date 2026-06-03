<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueResolution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueResolution>
 */
class IssueResolutionFactory extends Factory
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
            'user_id' => User::factory(),
            'is_satisfied' => fake()->boolean(80),
            'comment' => fake()->optional()->sentence(),
            'answered_at' => now(),
        ];
    }
}
