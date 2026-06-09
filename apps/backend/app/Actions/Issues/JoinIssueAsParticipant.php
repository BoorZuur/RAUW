<?php

namespace App\Actions\Issues;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\Issues\IssueRowLock;

class JoinIssueAsParticipant
{
    /**
     * Manually join a canonical issue as a participant.
     *
     * Idempotent when the user already participates (no second row, no counter bump).
     *
     * @return array{issue: Issue, created: bool}
     */
    public function join(User $user, Issue $canonical): array
    {
        return IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical) use ($user): array {
            $alreadyParticipant = IssueParticipant::query()
                ->where('issue_id', $lockedCanonical->getKey())
                ->where('user_id', $user->getKey())
                ->exists();

            if ($alreadyParticipant) {
                return [
                    'issue' => $lockedCanonical,
                    'created' => false,
                ];
            }

            IssueParticipant::query()->create([
                'issue_id' => $lockedCanonical->getKey(),
                'user_id' => $user->getKey(),
                'joined_via' => JoinedVia::Manual,
                'via_issue_id' => null,
                'joined_at' => now(),
            ]);

            $lockedCanonical->increment('participant_count');

            return [
                'issue' => $lockedCanonical,
                'created' => true,
            ];
        });
    }
}
