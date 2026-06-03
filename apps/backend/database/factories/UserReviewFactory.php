<?php

namespace Database\Factories;

use App\Models\Manager;
use App\Models\User;
use App\Models\UserReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserReview>
 */
class UserReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reviewed_by_manager_id' => Manager::factory(),
            'reason' => fake()->randomElement(['repeated_flags', 'manual_review', 'abuse_report']),
            'outcome' => 'pending',
            'note' => fake()->optional()->sentence(),
            'created_at' => now(),
            'resolved_at' => null,
        ];
    }
}
