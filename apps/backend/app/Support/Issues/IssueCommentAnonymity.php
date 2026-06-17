<?php

namespace App\Support\Issues;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueParticipant;
use App\Models\User;

class IssueCommentAnonymity
{
    public function __construct(
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
        private readonly IssueChatAliasResolver $aliasResolver = new IssueChatAliasResolver,
    ) {}

    /**
     * Whether the comment toggle should default to anonymous for this user on the issue.
     */
    public function defaultForUserOnIssue(User $user, Issue $issue): bool
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);

        if ($canonical->user_id === $user->getKey() && (bool) $canonical->is_anonymous === true) {
            return true;
        }

        return IssueParticipant::query()
            ->where('issue_id', $canonical->getKey())
            ->where('user_id', $user->getKey())
            ->where('is_anonymous', true)
            ->exists();
    }

    /**
     * Whether the comment author should be redacted in API responses.
     */
    public function shouldRedact(IssueComment $comment): bool
    {
        return $comment->author_type === ActorType::User
            && (bool) $comment->is_anonymous === true;
    }

    /**
     * Resolve the display name for a user-authored comment.
     *
     * @return array<string, mixed>
     */
    public function resolveDisplayName(IssueComment $comment): array
    {
        $user = $comment->relationLoaded('user')
            ? $comment->getRelation('user')
            : $comment->user()->first();

        if (! $user instanceof User) {
            return [
                'is_anonymous' => true,
                'display_name' => 'Buurtbewoner',
            ];
        }

        $canonical = $this->resolveCanonicalForComment($comment);
        $resolved = $this->aliasResolver->forUserOnIssue($user, $canonical);

        if ((bool) ($resolved['is_anonymous'] ?? false) === true) {
            return [
                'is_anonymous' => true,
                'display_name' => $resolved['display_name'],
            ];
        }

        return [
            'is_anonymous' => true,
            'display_name' => $this->hashAlias((int) $user->getKey(), (int) $canonical->getKey()),
        ];
    }

    /**
     * Stable hash-based Deelnemer# alias for non-participant anonymous commenters.
     */
    public function hashAlias(int $userId, int $canonicalIssueId): string
    {
        $suffix = str_pad(
            (string) (crc32("{$userId}:{$canonicalIssueId}") % 1_000_000),
            6,
            '0',
            STR_PAD_LEFT,
        );

        return 'Deelnemer#'.$suffix;
    }

    private function resolveCanonicalForComment(IssueComment $comment): Issue
    {
        if ($comment->relationLoaded('issue')) {
            $issue = $comment->getRelation('issue');

            if ($issue instanceof Issue) {
                return ($this->resolveCanonicalIssue)($issue);
            }
        }

        $issue = Issue::query()->findOrFail($comment->issue_id);

        return ($this->resolveCanonicalIssue)($issue);
    }
}
