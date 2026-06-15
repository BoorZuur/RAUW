<?php

namespace App\Support\Issues;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

class IssueAnonymousDisplayName
{
    /**
     * Request-scoped participant cache keyed by "{issue_id}:{user_id}".
     * Null when preload has not run; empty array after preload clears prior state.
     *
     * @var array<string, IssueParticipant|null>|null
     */
    private static ?array $participantMap = null;

    /**
     * Batch-load issue participants for feedback rows to avoid N+1 queries.
     *
     * @param  Collection<int, \App\Models\IssueFeedback>  $feedbacks
     */
    public static function preloadForFeedbacks(Collection $feedbacks): void
    {
        self::$participantMap = [];

        if ($feedbacks->isEmpty()) {
            return;
        }

        $issueIds = $feedbacks->pluck('issue_id')->unique()->values()->all();
        $userIds = $feedbacks->pluck('reviewer_user_id')->unique()->values()->all();

        if ($issueIds === [] || $userIds === []) {
            return;
        }

        $participants = IssueParticipant::query()
            ->whereIn('issue_id', $issueIds)
            ->whereIn('user_id', $userIds)
            ->with('viaIssue')
            ->get();

        foreach ($participants as $participant) {
            self::$participantMap["{$participant->issue_id}:{$participant->user_id}"] = $participant;
        }

        foreach ($feedbacks as $feedback) {
            $key = "{$feedback->issue_id}:{$feedback->reviewer_user_id}";
            if (! array_key_exists($key, self::$participantMap)) {
                self::$participantMap[$key] = null;
            }
        }
    }

    /**
     * Derive the reviewer shape array according to anonymity rules.
     */
    public static function derive(User $reviewer, Issue $issue): array
    {
        $key = "{$issue->id}:{$reviewer->id}";

        if (self::$participantMap !== null && array_key_exists($key, self::$participantMap)) {
            $participant = self::$participantMap[$key];
        } else {
            $participant = IssueParticipant::query()
                ->where('issue_id', $issue->id)
                ->where('user_id', $reviewer->id)
                ->first();
        }

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
