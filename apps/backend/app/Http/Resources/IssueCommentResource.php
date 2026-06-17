<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueCommentAnonymity;
use App\Support\Issues\IssueCommentPolicyFlags;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes issue comments with ownership-safe author redaction.
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
        $policyFlags = IssueCommentPolicyFlags::for($comment, $request->user());
        $anonymity = new IssueCommentAnonymity;

        return [
            'id' => $comment->id,
            'issue_id' => $comment->issue_id,
            'author_type' => $comment->author_type->value,
            'content' => $comment->content,
            'is_anonymous' => (bool) $comment->is_anonymous,
            'is_flagged' => (bool) $comment->is_flagged,
            'visibility' => $comment->visibility->value,
            'author' => $this->compactAuthor($comment, $anonymity),
            'can_update' => $policyFlags['can_update'],
            'can_delete' => $policyFlags['can_delete'],
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ];
    }

    /**
     * Return an author summary based on author_type.
     *
     * User comments with is_anonymous expose only the stable alias and never
     * the user's identity. Officer and manager comments always expose real
     * identity. All viewers see the same redacted shape.
     *
     * Eager-load issue (canonical) for user-authored redaction; user, officer,
     * or manager for full author details.
     *
     * @return array<string, mixed>
     */
    protected function compactAuthor(IssueComment $comment, IssueCommentAnonymity $anonymity): array
    {
        if ($comment->author_type === ActorType::User) {
            if ($anonymity->shouldRedact($comment)) {
                return $anonymity->resolveDisplayName($comment);
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
}
