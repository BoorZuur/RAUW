<?php

namespace App\Http\Requests\IssueChats;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class IndexIssueChatsRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * Active users and officers may list chats. Managers are excluded.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor instanceof Manager) {
            return false;
        }

        if ($actor instanceof Officer && (bool) $actor->is_active === true) {
            return true;
        }

        return $actor instanceof User && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
