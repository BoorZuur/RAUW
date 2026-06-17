<?php

namespace Database\Factories;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\Officer;
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
            'community_post_id' => null,
            'type' => fake()->randomElement(NotificationType::cases()),
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(),
            'payload' => null,
            'dedup_key' => null,
            'actor_type' => null,
            'actor_id' => null,
            'is_read' => false,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_read' => true,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_read' => false,
        ]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'recipient_type' => ActorType::User,
            'user_id' => $user?->id ?? User::factory(),
            'officer_id' => null,
            'manager_id' => null,
        ]);
    }

    public function forOfficer(?Officer $officer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'recipient_type' => ActorType::Officer,
            'officer_id' => $officer?->id ?? Officer::factory(),
            'user_id' => null,
            'manager_id' => null,
        ]);
    }

    public function statusChange(): static
    {
        return $this->ofType(NotificationType::StatusChange);
    }

    public function newMessage(): static
    {
        return $this->ofType(NotificationType::NewMessage);
    }

    public function newIssue(): static
    {
        return $this->ofType(NotificationType::NewIssue);
    }

    public function chatOpened(): static
    {
        return $this->ofType(NotificationType::ChatOpened);
    }

    public function chatClosed(): static
    {
        return $this->ofType(NotificationType::ChatClosed);
    }

    public function newComment(): static
    {
        return $this->ofType(NotificationType::NewComment);
    }

    public function resolutionPosted(): static
    {
        return $this->ofType(NotificationType::ResolutionPosted);
    }

    public function feedbackReceived(): static
    {
        return $this->ofType(NotificationType::FeedbackReceived);
    }

    public function newCommunityPost(): static
    {
        return $this->ofType(NotificationType::NewCommunityPost);
    }

    public function issueHidden(): static
    {
        return $this->ofType(NotificationType::IssueHidden);
    }

    public function ofType(NotificationType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }
}
