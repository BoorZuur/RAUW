<?php

namespace App\Support;

use App\Enums\ActorType;
use App\Enums\Visibility;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommentVisibilityQuery
{
    /**
     * Restrict comment queries to rows the actor may list or show.
     *
     * Active users see comments with `visibility = visible` or comments they wrote
     * (`issue_comments.user_id` where `author_type = user`). Active officers and
     * managers see all comments including hidden. Assumes the actor is active;
     * inactive actors are blocked by middleware.
     */
    public static function applyVisibilityScope(Builder $query, Model $actor): Builder
    {
        if ($actor instanceof Officer || $actor instanceof Manager) {
            return $query;
        }

        if ($actor instanceof User) {
            return $query->where(function (Builder $scoped) use ($actor): void {
                $scoped->where('visibility', Visibility::Visible)
                    ->orWhere(function (Builder $authorScoped) use ($actor): void {
                        $authorScoped->where('author_type', ActorType::User)
                            ->where('user_id', $actor->getKey());
                    });
            });
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Whether the actor may view a single comment.
     *
     * Mirrors {@see applyVisibilityScope()} for one row: users may view visible
     * comments or their own comments; officers and managers may view any comment.
     */
    public static function canViewComment(IssueComment $comment, Model $actor): bool
    {
        if ($actor instanceof Officer || $actor instanceof Manager) {
            return true;
        }

        if ($actor instanceof User) {
            return $comment->visibility === Visibility::Visible
                || ($comment->author_type === ActorType::User && $comment->user_id === $actor->getKey());
        }

        return false;
    }
}
