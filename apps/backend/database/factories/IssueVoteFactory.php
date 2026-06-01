<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueVote>
 */
class IssueVoteFactory extends Factory
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
            'voted_at' => now(),
        ];
    }
}
