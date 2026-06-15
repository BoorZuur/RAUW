<?php

namespace App\Support\Issues;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;

class IssueChatAliasResolver
{
    /**
     * Resolve the display alias for a user on a canonical issue.
     *
     * Mirrors {@see \App\Http\Resources\IssueCommentResource} owner redaction and
     * {@see \App\Http\Resources\IssueParticipantResource} participant aliases.
     *
     * @return array<string, mixed>
     */
    public function forUserOnIssue(User $user, Issue $canonical): array
    {
        if ($canonical->user_id === $user->getKey()) {
            if ((bool) $canonical->is_anonymous === true && $canonical->anonymous_alias !== null) {
                return [
                    'is_anonymous' => true,
                    'display_name' => $canonical->anonymous_alias,
                ];
            }

            return [
                'is_anonymous' => false,
                'id' => $user->getKey(),
                'username' => $user->username,
                'display_name' => $user->username,
            ];
        }

        $participant = IssueParticipant::query()
            ->where('issue_id', $canonical->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($participant === null) {
            return [
                'is_anonymous' => false,
                'id' => $user->getKey(),
                'username' => $user->username,
                'display_name' => $user->username,
            ];
        }

        if ((bool) $participant->is_anonymous === true) {
            return [
                'is_anonymous' => true,
                'display_name' => $this->resolveAnonymousDisplayName($participant, $canonical),
            ];
        }

        return [
            'is_anonymous' => false,
            'id' => $user->getKey(),
            'username' => $user->username,
            'display_name' => $user->username,
        ];
    }

    protected function resolveAnonymousDisplayName(IssueParticipant $participant, Issue $canonical): string
    {
        if (
            $participant->joined_via === JoinedVia::Creator
            && (bool) $canonical->is_anonymous === true
            && $canonical->anonymous_alias !== null
        ) {
            return $canonical->anonymous_alias;
        }

        if ($participant->via_issue_id !== null) {
            $viaIssue = $participant->viaIssue;

            if (
                $viaIssue !== null
                && (bool) $viaIssue->is_anonymous === true
                && $viaIssue->anonymous_alias !== null
            ) {
                return $viaIssue->anonymous_alias;
            }
        }

        return 'Deelnemer#'.str_pad((string) $participant->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
