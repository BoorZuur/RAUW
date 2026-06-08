<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class IndexManagerRequest extends FormRequest
{
    /**
     * The default number of managers returned per page when `per_page` is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Only an authenticated, active main manager may list ordinary managers.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Listing is restricted to a Manager
     * whose `is_active` and `is_main_manager` flags are both true, so users,
     * officers, non-main managers, and inactive managers are all rejected
     * with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true
            && (bool) $actor->is_main_manager === true;
    }

    /**
     * Validation rules for manager list pagination.
     *
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
     * The validated, bounded number of managers to return per page.
     */
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
