<?php

namespace Database\Factories;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueParticipant>
 */
class IssueParticipantFactory extends Factory
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
            'joined_via' => JoinedVia::Manual,
            'via_issue_id' => null,
            'joined_at' => now(),
        ];
    }

    public function creator(): static
    {
        return $this->state(fn (): array => [
            'joined_via' => JoinedVia::Creator,
            'via_issue_id' => null,
        ]);
    }
}
