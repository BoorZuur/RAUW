<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\Issues\IssueDuplicateConflict;
use App\Support\Issues\IssueRowLock;

class LeaveIssueParticipation
{
    /**
     * Remove the actor's participation row on a canonical issue and decrement the counter.
     */
    public function leave(User $user, Issue $canonical): void
    {
        IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical) use ($user): void {
            $participant = IssueParticipant::query()
                ->where('issue_id', $lockedCanonical->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if ($participant === null) {
                throw IssueDuplicateConflict::notParticipant();
            }

            $participant->delete();

            if ($lockedCanonical->participant_count > 0) {
                $lockedCanonical->decrement('participant_count');
            }
        });
    }
}
