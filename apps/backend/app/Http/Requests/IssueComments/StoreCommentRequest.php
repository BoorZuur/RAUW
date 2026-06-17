<?php

namespace App\Http\Requests\IssueComments;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Only an authenticated, active user, officer, or manager may create comments.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return ($actor instanceof User || $actor instanceof Officer || $actor instanceof Manager)
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for creating a comment.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }
}
