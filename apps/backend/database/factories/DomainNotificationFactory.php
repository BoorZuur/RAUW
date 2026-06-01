<?php

namespace Database\Factories;

use App\Enums\ActorType;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DomainNotification>
 */
class DomainNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_type' => ActorType::User,
            'user_id' => User::factory(),
            'officer_id' => null,
            'manager_id' => null,
            'issue_id' => Issue::factory(),
            'type' => fake()->randomElement(['issue_created', 'issue_updated', 'comment_added']),
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(),
            'is_read' => false,
            'created_at' => now(),
        ];
    }
}
