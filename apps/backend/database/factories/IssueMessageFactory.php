<?php

namespace Database\Factories;

use App\Enums\ActorType;
use App\Models\Issue;
use App\Models\IssueMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueMessage>
 */
class IssueMessageFactory extends Factory
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
            'sender_type' => ActorType::User,
            'user_id' => User::factory(),
            'officer_id' => null,
            'content' => fake()->paragraph(),
            'is_flagged' => false,
            'is_read' => false,
            'created_at' => now(),
        ];
    }
}
