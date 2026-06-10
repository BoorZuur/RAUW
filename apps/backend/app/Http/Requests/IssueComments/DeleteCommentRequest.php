<?php

namespace App\Http\Requests\IssueComments;

use App\Enums\ActorType;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
{
    /**
     * Only the comment author or an active manager may delete the comment.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null || (bool) $actor->is_active !== true) {
            return false;
        }

        if ($actor instanceof Manager) {
            return true;
        }

        $comment = $this->route('comment');

        if (! $comment instanceof IssueComment) {
            return false;
        }

        if ($actor instanceof User) {
            return $comment->author_type === ActorType::User
                && $comment->user_id === $actor->getKey();
        }

        if ($actor instanceof Officer) {
            return $comment->author_type === ActorType::Officer
                && $comment->officer_id === $actor->getKey();
        }

        return false;
    }

    /**
     * Validation rules for deleting a comment.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
