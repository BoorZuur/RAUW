<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use InvalidArgumentException;

readonly class NotificationInsert
{
    public function __construct(
        public ActorType $recipientType,
        public NotificationType $type,
        public string $title,
        public ?int $userId = null,
        public ?int $officerId = null,
        public ?int $managerId = null,
        public ?int $issueId = null,
        public ?int $communityPostId = null,
        public ?string $body = null,
        public ?array $payload = null,
        public ?string $dedupKey = null,
        public ?ActorType $actorType = null,
        public ?int $actorId = null,
    ) {
        self::validateRecipient($recipientType, $userId, $officerId, $managerId);
    }

    public function recipientId(): int
    {
        return match ($this->recipientType) {
            ActorType::User => $this->userId,
            ActorType::Officer => $this->officerId,
            ActorType::Manager => $this->managerId,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toRowArray(): array
    {
        $now = now();

        return [
            'recipient_type' => $this->recipientType->value,
            'user_id' => $this->userId,
            'officer_id' => $this->officerId,
            'manager_id' => $this->managerId,
            'issue_id' => $this->issueId,
            'community_post_id' => $this->communityPostId,
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload !== null ? json_encode($this->payload) : null,
            'dedup_key' => $this->dedupKey,
            'actor_type' => $this->actorType?->value,
            'actor_id' => $this->actorId,
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private static function validateRecipient(
        ActorType $recipientType,
        ?int $userId,
        ?int $officerId,
        ?int $managerId,
    ): void {
        $filled = array_filter([
            ActorType::User->value => $userId,
            ActorType::Officer->value => $officerId,
            ActorType::Manager->value => $managerId,
        ], static fn (?int $id): bool => $id !== null);

        if (count($filled) !== 1) {
            throw new InvalidArgumentException('Exactly one recipient foreign key must be set.');
        }

        if (! array_key_exists($recipientType->value, $filled)) {
            throw new InvalidArgumentException('Recipient foreign key does not match recipient type.');
        }
    }
}
