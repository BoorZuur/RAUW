<?php

namespace App\Support\Issues;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;

class IssueDuplicateAssertions
{
    public static function assertCanonical(Issue $issue): void
    {
        if ($issue->duplicate_of_id !== null) {
            throw IssueDuplicateConflict::issueNotCanonical();
        }
    }

    public static function assertMatchableStatus(Issue $issue): void
    {
        if (! in_array($issue->status, [IssueStatus::Open, IssueStatus::InProgress], true)) {
            throw IssueDuplicateConflict::issueNotMatchable();
        }
    }

    public static function assertNotSelfDuplicate(User $user, Issue $canonical): void
    {
        if ($canonical->user_id === $user->getKey()) {
            throw IssueDuplicateConflict::cannotDuplicateSelf();
        }
    }

    public static function assertNotAlreadyChild(Issue $issue): void
    {
        if ($issue->duplicate_of_id !== null) {
            throw IssueDuplicateConflict::issueIsDuplicateChild();
        }
    }
}
