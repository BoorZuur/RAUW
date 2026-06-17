<?php

namespace App\Support\Issues;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueFeedback;
use Illuminate\Support\Carbon;

class IssueFeedbackWindow
{
    /**
     * Returns the timestamp when the issue was last closed.
     */
    public static function geslotenAt(Issue $issue): ?Carbon
    {
        // Latest issue_status_histories.changed_at where new_status = gesloten
        $history = $issue->statusHistory()
            ->where('new_status', IssueStatus::Closed)
            ->latest('changed_at')
            ->first();

        if ($history) {
            return $history->changed_at;
        }

        // Fallback E3: if status is gesloten, but no history row exists, use updated_at
        if ($issue->status === IssueStatus::Closed) {
            return $issue->updated_at;
        }

        return null;
    }

    /**
     * Is the issue within the 7-day feedback window?
     */
    public static function isOpen(Issue $issue): bool
    {
        if ($issue->status !== IssueStatus::Closed) {
            return false;
        }

        $geslotenAt = self::geslotenAt($issue);

        if (! $geslotenAt) {
            return false;
        }

        // Inclusive 7x24 hours
        return now()->lessThanOrEqualTo($geslotenAt->copy()->addDays(7));
    }

    /**
     * Is the given feedback still within its 24-hour edit window?
     */
    public static function isEditable(IssueFeedback $feedback): bool
    {
        return now()->lessThanOrEqualTo($feedback->submitted_at->copy()->addDay());
    }
}
