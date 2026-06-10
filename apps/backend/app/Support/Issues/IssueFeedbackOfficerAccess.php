<?php

namespace App\Support\Issues;

use App\Models\Issue;
use App\Models\Officer;

class IssueFeedbackOfficerAccess
{
    /**
     * Determine if an officer has ever been assigned to this issue
     * or authored a resolution for it.
     */
    public static function hasInvolvement(Officer $officer, Issue $issue): bool
    {
        // 1. Ever assignee
        $hasHistory = $issue->officerAssignmentHistories()
            ->where('officer_id', $officer->id)
            ->exists();

        if ($hasHistory) {
            return true;
        }

        // 2. Resolution author
        $hasResolution = $issue->officerResolution()
            ->where('officer_id', $officer->id)
            ->exists();

        return $hasResolution;
    }
}
