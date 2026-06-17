<?php

namespace Database\Factories;

use App\Enums\ActorType;
use App\Enums\Visibility;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueComment>
 */
class IssueCommentFactory extends Factory
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
            'author_type' => ActorType::User,
            'user_id' => User::factory(),
            'officer_id' => null,
            'content' => fake()->paragraph(),
            'is_anonymous' => false,
            'is_flagged' => false,
            'visibility' => Visibility::Visible,
        ];
    }

    public function forManager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_type' => ActorType::Manager,
            'manager_id' => Manager::factory(),
            'user_id' => null,
            'officer_id' => null,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (): array => [
            'is_anonymous' => true,
        ]);
    }
}
