<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Issue comment API resource.
 *
 * Author anonymity: user-authored comments on anonymous issues by the issue
 * owner are redacted at the response layer — all viewers see the issue's
 * stable alias instead of the real username or id. Officer and manager
 * authors always expose real identity.
 *
 * @mixin IssueComment
 */
class IssueCommentResource extends JsonResource
{
    /**
     * Disable wrapping so collections and single resources share a flat shape.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueComment $comment */
        $comment = $this->resource;

        return [
            'id' => $comment->id,
            'issue_id' => $comment->issue_id,
            'author_type' => $comment->author_type->value,
            'content' => $comment->content,
            'is_flagged' => (bool) $comment->is_flagged,
            'visibility' => $comment->visibility->value,
            'author' => $this->compactAuthor($comment),
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ];
    }

    /**
     * Return an author summary based on author_type.
     *
     * User authors on anonymous issues owned by that user are redacted to the
     * issue alias when the `issue` relation is loaded. Otherwise eager-loaded
     * user, officer, or manager details are exposed to prevent N+1 queries.
     *
     * @return array<string, mixed>
     */
    protected function compactAuthor(IssueComment $comment): array
    {
        if ($comment->author_type === ActorType::User) {
            if ($this->shouldRedactAsAnonymous($comment)) {
                /** @var Issue $issue */
                $issue = $comment->getRelation('issue');

                return [
                    'is_anonymous' => true,
                    'display_name' => $issue->anonymous_alias,
                ];
            }

            if ($comment->relationLoaded('user')) {
                $user = $comment->getRelation('user');

                if ($user instanceof User) {
                    return [
                        'is_anonymous' => false,
                        'id' => $user->id,
                        'username' => $user->username,
                        'display_name' => $user->username,
                    ];
                }
            }

            return [
                'is_anonymous' => false,
                'id' => $comment->user_id,
            ];
        }

        if ($comment->author_type === ActorType::Officer) {
            if ($comment->relationLoaded('officer')) {
                $officer = $comment->getRelation('officer');

                if ($officer instanceof Officer) {
                    return [
                        'is_anonymous' => false,
                        'id' => $officer->id,
                        'username' => $officer->username,
                        'display_name' => $officer->username,
                    ];
                }
            }

            return [
                'is_anonymous' => false,
                'id' => $comment->officer_id,
            ];
        }

        if ($comment->author_type === ActorType::Manager) {
            if ($comment->relationLoaded('manager')) {
                $manager = $comment->getRelation('manager');

                if ($manager instanceof Manager) {
                    return [
                        'is_anonymous' => false,
                        'id' => $manager->id,
                        'username' => $manager->username,
                        'display_name' => $manager->username,
                    ];
                }
            }

            return [
                'is_anonymous' => false,
                'id' => $comment->manager_id,
            ];
        }

        return [];
    }

    /**
     * Whether a user-authored comment should expose only the issue alias.
     */
    protected function shouldRedactAsAnonymous(IssueComment $comment): bool
    {
        if (! $comment->relationLoaded('issue')) {
            return false;
        }

        $issue = $comment->getRelation('issue');

        if (! $issue instanceof Issue) {
            return false;
        }

        return $comment->author_type === ActorType::User
            && (bool) $issue->is_anonymous === true
            && $comment->user_id === $issue->user_id;
    }
}
