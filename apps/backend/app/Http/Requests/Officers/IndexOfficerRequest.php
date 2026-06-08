<?php

namespace App\Http\Requests\Officers;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOfficerRequest extends FormRequest
{
    /**
     * The default number of officers returned per page when `per_page` is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Only an authenticated, active officer or manager may list officers.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Listing is restricted to an active
     * Officer or an active Manager; users and inactive actors receive a 403
     * response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor instanceof Officer) {
            return (bool) $actor->is_active === true;
        }

        if ($actor instanceof Manager) {
            return (bool) $actor->is_active === true;
        }

        return false;
    }

    /**
     * Validation rules for officer list filters and pagination.
     *
     * The optional `is_active` query parameter follows the same truthy convention
     * as `IndexIssueRequest::wantsMine()`: `true`/`1` restrict to active officers,
     * `false`/`0` restrict to inactive officers, and when omitted the list
     * defaults to active officers only. Soft-deleted officers are always excluded
     * by the Officer model's soft-delete scope.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'district_id' => ['sometimes', 'integer', Rule::exists('districts', 'id')],
            'department_id' => ['sometimes', 'integer', Rule::exists('departments', 'id')],
            'is_active' => ['sometimes', Rule::in(['0', 'false', false, 0, '1', 'true', true, 1])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /**
     * Whether the client requested only active officers.
     *
     * When `is_active` is omitted, the list defaults to active officers only.
     */
    public function wantsActiveOfficers(): bool
    {
        if (! $this->filled('is_active')) {
            return true;
        }

        return in_array($this->input('is_active'), ['1', 'true', true, 1], true);
    }

    /**
     * The validated, bounded number of officers to return per page.
     */
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
