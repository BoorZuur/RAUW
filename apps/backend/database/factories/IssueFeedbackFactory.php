<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IssueFeedback>
 */
class IssueFeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'reviewer_user_id' => User::factory(),
            'is_satisfied' => fake()->boolean(),
            'comment' => fake()->optional()->paragraph(),
            'submitted_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'updated_at' => null,
        ];
    }
}
