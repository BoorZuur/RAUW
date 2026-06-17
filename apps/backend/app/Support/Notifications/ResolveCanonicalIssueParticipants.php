<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Models\Issue;
use Illuminate\Support\Collection;

class ResolveCanonicalIssueParticipants
{
    public function __construct(
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    /**
     * Unique participant user ids on the canonical issue (owner + issue_participants).
     *
     * @return Collection<int, int>
     */
    public function userIds(Issue $issue): Collection
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        $canonical->loadMissing('participants');

        return collect([$canonical->user_id])
            ->merge($canonical->participants->pluck('user_id'))
            ->filter()
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    public function assignedOfficerId(Issue $issue): ?int
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);

        return $canonical->assigned_officer_id !== null
            ? (int) $canonical->assigned_officer_id
            : null;
    }
}
