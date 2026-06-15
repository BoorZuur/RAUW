<?php

namespace App\Support\Notifications;

use App\Models\Issue;
use App\Models\IssueOfficerAssignmentHistory;

class ResolveFeedbackRecipientOfficer
{
    /**
     * Officer who should receive feedback notifications for the canonical issue.
     *
     * Priority: assigned officer, resolution author, most recent assignment history.
     */
    public function officerId(Issue $canonical): ?int
    {
        if ($canonical->assigned_officer_id !== null) {
            return (int) $canonical->assigned_officer_id;
        }

        $canonical->loadMissing('officerResolution');

        if ($canonical->officerResolution?->officer_id !== null) {
            return (int) $canonical->officerResolution->officer_id;
        }

        $latestOfficerId = IssueOfficerAssignmentHistory::query()
            ->where('issue_id', $canonical->getKey())
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->value('officer_id');

        return $latestOfficerId !== null ? (int) $latestOfficerId : null;
    }
}
