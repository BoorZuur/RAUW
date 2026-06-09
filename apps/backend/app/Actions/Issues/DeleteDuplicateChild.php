<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\Issues\IssueRowLock;

class DeleteDuplicateChild
{
    /**
     * Hard-delete a duplicate child and decrement counters on the canonical parent.
     *
     * When {@see $leaveParticipation} is false (default), the actor's participation
     * row on the canonical is kept and `via_issue_id` nulls via FK when the child
     * is removed. When true, the participation row is deleted and
     * `participant_count` is decremented.
     */
    public function delete(User $owner, Issue $child, bool $leaveParticipation = false): void
    {
        $canonical = Issue::query()->findOrFail($child->duplicate_of_id);

        IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical) use (
            $owner,
            $child,
            $leaveParticipation,
        ): void {
            if ($lockedCanonical->duplicate_count > 0) {
                $lockedCanonical->decrement('duplicate_count');
            }

            if ($leaveParticipation) {
                $participant = IssueParticipant::query()
                    ->where('issue_id', $lockedCanonical->getKey())
                    ->where('user_id', $owner->getKey())
                    ->first();

                if ($participant !== null) {
                    $participant->delete();

                    if ($lockedCanonical->participant_count > 0) {
                        $lockedCanonical->decrement('participant_count');
                    }
                }
            }

            $child->delete();
        });
    }
}
