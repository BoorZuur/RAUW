<?php

namespace App\Support\Issues;

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

    private static ?IssueChatAliasResolver $resolver = null;

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
        $resolved = self::resolver()->forUserOnIssue($reviewer, $issue);

        if ((bool) ($resolved['is_anonymous'] ?? false) === true) {
            return [
                'is_anonymous' => true,
                'display_name' => $resolved['display_name'],
            ];
        }

        return [
            'is_anonymous' => false,
            'id' => $reviewer->id,
            'username' => $reviewer->username,
        ];
    }

    private static function resolver(): IssueChatAliasResolver
    {
        return self::$resolver ??= new IssueChatAliasResolver;
    }
}
