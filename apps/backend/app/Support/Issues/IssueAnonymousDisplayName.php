<?php

namespace App\Support\Issues;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;

class IssueAnonymousDisplayName
{
    /**
     * Derive the reviewer shape array according to anonymity rules.
     */
    public static function derive(User $reviewer, Issue $issue): array
    {
        $participant = IssueParticipant::query()
            ->where('issue_id', $issue->id)
            ->where('user_id', $reviewer->id)
            ->first();

        $isAnonymous = false;
        $displayName = null;

        if ($participant) {
            $isAnonymous = $participant->is_anonymous;

            if ($isAnonymous) {
                if ($participant->joined_via === JoinedVia::Creator) {
                    if ($issue->is_anonymous) {
                        $displayName = $issue->anonymous_alias;
                    }
                } elseif ($participant->joined_via === JoinedVia::Duplicate && $participant->via_issue_id) {
                    $viaIssue = $participant->viaIssue;
                    if ($viaIssue && $viaIssue->is_anonymous && $viaIssue->anonymous_alias) {
                        $displayName = $viaIssue->anonymous_alias;
                    }
                }

                if (! $displayName) {
                    $displayName = 'Deelnemer#' . str_pad((string) $participant->id, 4, '0', STR_PAD_LEFT);
                }
            }
        } else {
            // Owner fallback (no participant row)
            if ($issue->user_id === $reviewer->id && $issue->is_anonymous) {
                $isAnonymous = true;
                $displayName = $issue->anonymous_alias;
            }
        }

        if ($isAnonymous) {
            return [
                'is_anonymous' => true,
                'display_name' => $displayName,
            ];
        }

        return [
            'is_anonymous' => false,
            'id' => $reviewer->id,
            'username' => $reviewer->username,
        ];
    }
}
