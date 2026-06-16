<?php

namespace App\Support\Issues;

use App\Enums\ActorType;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class IssueCommentPolicyFlags
{
    /**
     * @return array{can_update: bool, can_delete: bool}
     */
    public static function for(IssueComment $comment, ?Authenticatable $actor): array
    {
        if ($actor === null || (bool) $actor->is_active !== true) {
            return [
                'can_update' => false,
                'can_delete' => false,
            ];
        }

        if ($actor instanceof Manager) {
            return [
                'can_update' => self::isAuthor($comment, $actor),
                'can_delete' => true,
            ];
        }

        $isAuthor = self::isAuthor($comment, $actor);

        return [
            'can_update' => $isAuthor,
            'can_delete' => $isAuthor,
        ];
    }

    private static function isAuthor(IssueComment $comment, Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $comment->author_type === ActorType::User
                && $comment->user_id === $actor->getKey();
        }

        if ($actor instanceof Officer) {
            return $comment->author_type === ActorType::Officer
                && $comment->officer_id === $actor->getKey();
        }

        if ($actor instanceof Manager) {
            return $comment->author_type === ActorType::Manager
                && $comment->manager_id === $actor->getKey();
        }

        return false;
    }
}
