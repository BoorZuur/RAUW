<?php

namespace App\Support\Issues;

use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;

class IssueParticipantAccess
{
    /**
     * Determine if the user is an active participant or a legacy owner fallback.
     */
    public static function isParticipant(User $user, Issue $issue): bool
    {
        // 1. Check if the user has an active issue_participants row
        $hasRow = IssueParticipant::query()
            ->where('issue_id', $issue->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasRow) {
            return true;
        }

        // 2. Legacy owner fallback
        // Applied only when there are no participants AND the issue was created by this user
        if ($issue->participant_count === 0 && $issue->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Assert that the user is a participant or throw 403.
     */
    public static function assertParticipant(User $user, Issue $issue): void
    {
        if (! self::isParticipant($user, $issue)) {
            abort(403, 'not_issue_participant');
        }
    }
}
