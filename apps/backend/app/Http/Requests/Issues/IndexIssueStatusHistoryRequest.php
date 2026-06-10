<?php

namespace App\Http\Requests\Issues;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class IndexIssueStatusHistoryRequest extends FormRequest
{
    /**
     * The default number of status history rows returned per page when `per_page`
     * is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Only an authenticated, active officer or manager may list status history
     * for an issue.
     *
     * Users, inactive officers, inactive managers, and unauthenticated requests
     * are rejected with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor instanceof Officer && (bool) $actor->is_active === true) {
            return true;
        }

        return $actor instanceof Manager && (bool) $actor->is_active === true;
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

    /**
     * The validated, bounded number of status history rows to return per page.
     */
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
