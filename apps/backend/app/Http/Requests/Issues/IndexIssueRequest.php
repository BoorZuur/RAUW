<?php

namespace App\Http\Requests\Issues;

use App\Enums\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIssueRequest extends FormRequest
{
    /**
     * The default number of issues returned per page when `per_page` is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Listing issues is available to any authenticated actor; route middleware
     * enforces authentication, so no additional actor restriction is applied
     * here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the composable issue list filters and pagination.
     *
     * Filters are optional and composable: `district_id`, `department`, and
     * `category_id` may be combined to narrow the result set. Pagination is
     * bounded so `per_page` can never exceed a safe maximum.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'district_id' => ['sometimes', 'integer', Rule::exists('districts', 'id')],
            'department' => ['sometimes', Rule::in(Department::values())],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /**
     * The validated, bounded number of issues to return per page.
     */
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
