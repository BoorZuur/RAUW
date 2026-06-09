<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\IssueComment;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
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
     * Eager loads the user or officer details safely to prevent N+1 queries.
     *
     * @return array<string, mixed>
     */
    protected function compactAuthor(IssueComment $comment): array
    {
        if ($comment->author_type === ActorType::User) {
            if ($comment->relationLoaded('user')) {
                $user = $comment->getRelation('user');

                if ($user instanceof User) {
                    return [
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
                        'id' => $officer->id,
                        'username' => $officer->username,
                        'display_name' => $officer->username,
                    ];
                }
            }

            return [
                'id' => $comment->officer_id,
            ];
        }

        return [];
    }
}
