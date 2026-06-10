<?php

namespace Database\Factories;

use App\Enums\ChatStatus;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueChat>
 */
class IssueChatFactory extends Factory
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
            'status' => ChatStatus::Closed,
            'opened_by_officer_id' => null,
            'closed_by_officer_id' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => ChatStatus::Open,
        ]);
    }
}
