<?php

namespace App\Support\Issues;

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
        if (! $this->actor instanceof User) {
            return false;
        }

        $canonicalId = $this->canonicalIssueId();

        if ($canonicalId === null) {
            return false;
        }

        return IssueParticipant::query()
            ->where('issue_id', $canonicalId)
            ->where('user_id', $this->actor->getKey())
            ->exists();
    }

    public function canonicalIssueId(): ?int
    {
        if ($this->issue->duplicate_of_id === null) {
            return $this->issue->getKey();
        }

        return $this->issue->duplicate_of_id;
    }

    /**
     * Whether canonical title/content/location fields should be redacted for the actor.
     *
     * Full logic is completed in Phase 6; officers and managers are never redacted.
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

        $canonicalId = $this->canonicalIssueId();

        if ($canonicalId === null) {
            return false;
        }

        return IssueParticipant::query()
            ->where('issue_id', $canonicalId)
            ->where('user_id', $actor->getKey())
            ->exists();
    }
}
