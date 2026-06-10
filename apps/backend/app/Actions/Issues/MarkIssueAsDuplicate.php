<?php

namespace App\Actions\Issues;

use App\Enums\JoinedVia;
use App\Enums\Visibility;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Manager;
use App\Models\Officer;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateAssertions;
use App\Support\Issues\IssueDuplicateConflict;
use App\Support\Issues\IssueRowLock;

class MarkIssueAsDuplicate
{
    public function __construct(
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue,
    ) {}

    /**
     * Link an existing issue to a canonical target (re-parent) or merge when both
     * share the same owner.
     */
    public function mark(Officer|Manager $actor, Issue $child, int $duplicateOfId): Issue
    {
        if (! IssueVisibilityQuery::canViewIssue($child, $actor)) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        $target = Issue::query()->find($duplicateOfId);

        if ($target === null) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        if (! IssueVisibilityQuery::canViewIssue($target, $actor)) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        $canonical = $this->resolveCanonicalIssue->resolve($target);

        $this->assertPreLock($child, $canonical);

        return IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical) use ($child): Issue {
            $lockedChild = Issue::query()
                ->whereKey($child->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertPreLock($lockedChild, $lockedCanonical);

            if ($lockedChild->user_id === $lockedCanonical->user_id) {
                return $this->mergeSameOwnerIssue($lockedChild, $lockedCanonical);
            }

            return $this->reparentAsDuplicate($lockedChild, $lockedCanonical);
        });
    }

    private function assertPreLock(Issue $child, Issue $canonical): void
    {
        IssueDuplicateAssertions::assertNotSameIssue($child, $canonical);
        IssueDuplicateAssertions::assertNotAlreadyChild($child);
        IssueDuplicateAssertions::assertNoDuplicateChildren($child);
        IssueDuplicateAssertions::assertLinkableChild($child);
        IssueDuplicateAssertions::assertMatchableStatus($canonical);
    }

    private function reparentAsDuplicate(Issue $child, Issue $canonical): Issue
    {
        $child->update([
            'duplicate_of_id' => $canonical->getKey(),
            'visibility' => Visibility::Hidden,
        ]);

        $canonical->increment('duplicate_count');

        $alreadyParticipant = IssueParticipant::query()
            ->where('issue_id', $canonical->getKey())
            ->where('user_id', $child->user_id)
            ->exists();

        if (! $alreadyParticipant) {
            IssueParticipant::query()->create([
                'issue_id' => $canonical->getKey(),
                'user_id' => $child->user_id,
                'is_anonymous' => (bool) $child->is_anonymous,
                'joined_via' => JoinedVia::Duplicate,
                'via_issue_id' => $child->getKey(),
                'joined_at' => now(),
            ]);

            $canonical->increment('participant_count');
        }

        return $child->fresh();
    }

    private function mergeSameOwnerIssue(Issue $child, Issue $canonical): Issue
    {
        $this->migrateParticipants($child, $canonical);

        $canonical->update([
            'participant_count' => IssueParticipant::query()
                ->where('issue_id', $canonical->getKey())
                ->count(),
        ]);

        $child->delete();

        return $canonical->fresh();
    }

    /**
     * Move participant rows from the child issue to the canonical issue.
     *
     * When a user already participates on the canonical issue, the child row is
     * discarded to satisfy the unique `(issue_id, user_id)` constraint.
     */
    private function migrateParticipants(Issue $from, Issue $to): void
    {
        $participants = IssueParticipant::query()
            ->where('issue_id', $from->getKey())
            ->get();

        foreach ($participants as $participant) {
            $existsOnTarget = IssueParticipant::query()
                ->where('issue_id', $to->getKey())
                ->where('user_id', $participant->user_id)
                ->exists();

            if ($existsOnTarget) {
                $participant->delete();

                continue;
            }

            $participant->update(['issue_id' => $to->getKey()]);
        }
    }
}
