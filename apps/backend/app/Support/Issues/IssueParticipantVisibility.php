<?php

namespace App\Support\Issues;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class IssueParticipantVisibility
{
    public function __construct(
        private readonly Issue $issue,
        private readonly Authenticatable|null $actor = null,
    ) {}

    public static function for(Issue $issue, ?Authenticatable $actor): self
    {
        return new self($issue, $actor);
    }

    public function isDuplicateChild(): bool
    {
        return $this->issue->duplicate_of_id !== null;
    }

    public function isOwner(): bool
    {
        if (! $this->actor instanceof User) {
            return false;
        }

        return $this->issue->user_id === $this->actor->getKey();
    }

    public function isParticipant(): bool
    {
        return $this->resolveActorParticipant() !== null;
    }

    public function canonicalIssueId(): ?int
    {
        if ($this->issue->duplicate_of_id === null) {
            return $this->issue->getKey();
        }

        return $this->issue->duplicate_of_id;
    }

    public function participantJoinedVia(): ?string
    {
        $participant = $this->resolveActorParticipant();

        return $participant?->joined_via instanceof JoinedVia
            ? $participant->joined_via->value
            : null;
    }

    public function viaIssueId(): ?int
    {
        return $this->resolveActorParticipant()?->via_issue_id;
    }

    /**
     * Participation context flags for regular users.
     *
     * @return array<string, mixed>
     */
    public function contextFlags(): array
    {
        if (! $this->actor instanceof User) {
            return [];
        }

        return [
            'is_duplicate_child' => $this->isDuplicateChild(),
            'canonical_issue_id' => $this->isDuplicateChild() ? $this->canonicalIssueId() : null,
            'is_participant' => $this->isParticipant(),
            'participant_joined_via' => $this->participantJoinedVia(),
            'via_issue_id' => $this->viaIssueId(),
        ];
    }

    /**
     * Whether canonical title/content/location fields should be redacted for the actor.
     *
     * Participants viewing a canonical they joined (but do not own) receive status and
     * officer resolution only. Officers and managers are never redacted; owners and
     * duplicate-child owners always receive the full payload.
     */
    public function shouldRedactCanonicalContent(?Authenticatable $actor = null): bool
    {
        $actor ??= $this->actor;

        if ($actor instanceof Officer || $actor instanceof Manager) {
            return false;
        }

        if (! $actor instanceof User) {
            return false;
        }

        if ($this->isDuplicateChild()) {
            return false;
        }

        if ($this->issue->user_id === $actor->getKey()) {
            return false;
        }

        return $this->isParticipant();
    }

    /**
     * Whether `duplicate_of_id` may be exposed to the actor.
     */
    public function canExposeDuplicateOfId(): bool
    {
        if ($this->actor instanceof Officer || $this->actor instanceof Manager) {
            return true;
        }

        return $this->actor instanceof User
            && $this->isDuplicateChild()
            && $this->isOwner();
    }

    private function resolveActorParticipant(): ?IssueParticipant
    {
        if (! $this->actor instanceof User) {
            return null;
        }

        if ($this->issue->relationLoaded('actorParticipant')) {
            $participant = $this->issue->getRelation('actorParticipant');

            return $participant instanceof IssueParticipant ? $participant : null;
        }

        return IssueParticipant::query()
            ->where('issue_id', $this->canonicalIssueId())
            ->where('user_id', $this->actor->getKey())
            ->first();
    }
}
