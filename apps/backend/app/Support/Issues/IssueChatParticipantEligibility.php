<?php

namespace App\Support\Issues;

use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\IssueChatConflict;

class IssueChatParticipantEligibility
{
    /**
     * Whether the user may be the citizen partner in a chat on the canonical issue.
     */
    public static function isEligible(User $user, Issue $canonical): bool
    {
        if ($canonical->user_id === $user->getKey()) {
            return true;
        }

        return IssueParticipant::query()
            ->where('issue_id', $canonical->getKey())
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /**
     * Assert the user is the canonical owner or an issue participant.
     */
    public static function assertEligible(User $user, Issue $canonical): void
    {
        if (! self::isEligible($user, $canonical)) {
            throw IssueChatConflict::chatUserNotEligible();
        }
    }
}
