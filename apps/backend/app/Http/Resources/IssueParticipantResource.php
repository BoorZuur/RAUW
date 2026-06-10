<?php

namespace App\Http\Resources;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an issue participant for officer and manager list endpoints.
 *
 * Anonymous participants never expose `user_id` or identifying user fields;
 * a stable display alias is derived from the canonical issue, duplicate child,
 * or participant id.
 *
 * @mixin IssueParticipant
 */
class IssueParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueParticipant $participant */
        $participant = $this->resource;

        return [
            'id' => $participant->id,
            'joined_via' => $participant->joined_via instanceof JoinedVia
                ? $participant->joined_via->value
                : $participant->joined_via,
            'via_issue_id' => $participant->via_issue_id,
            'joined_at' => $participant->joined_at,
            'is_anonymous' => (bool) $participant->is_anonymous,
            'user' => $this->compactUser($participant),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function compactUser(IssueParticipant $participant): array
    {
        if ((bool) $participant->is_anonymous === true) {
            return [
                'is_anonymous' => true,
                'display_name' => $this->resolveAnonymousDisplayName($participant),
            ];
        }

        if ($participant->relationLoaded('user')) {
            $user = $participant->getRelation('user');

            if ($user instanceof User) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                ];
            }
        }

        return [
            'id' => $participant->user_id,
        ];
    }

    protected function resolveAnonymousDisplayName(IssueParticipant $participant): string
    {
        if (
            $participant->joined_via === JoinedVia::Creator
            && $participant->relationLoaded('issue')
        ) {
            $issue = $participant->getRelation('issue');

            if (
                $issue instanceof Issue
                && (bool) $issue->is_anonymous === true
                && $issue->anonymous_alias !== null
            ) {
                return $issue->anonymous_alias;
            }
        }

        if (
            $participant->via_issue_id !== null
            && $participant->relationLoaded('viaIssue')
        ) {
            $viaIssue = $participant->getRelation('viaIssue');

            if (
                $viaIssue instanceof Issue
                && (bool) $viaIssue->is_anonymous === true
                && $viaIssue->anonymous_alias !== null
            ) {
                return $viaIssue->anonymous_alias;
            }
        }

        return 'Deelnemer#'.str_pad((string) $participant->id, 6, '0', STR_PAD_LEFT);
    }
}
