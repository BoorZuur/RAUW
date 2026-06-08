<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueStatus;
use App\Enums\Visibility;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Contracts\Validation\Validator;
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
     * Listing issues is available to any authenticated active actor; route
     * middleware enforces authentication. Results are visibility-scoped in the
     * controller: users see visible issues or their own issues (any visibility);
     * officers and managers see all issues including hidden.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the composable issue list filters and pagination.
     *
     * List results are visibility-scoped per actor type before these filters
     * (users: visible issues or own issues; officers/managers: all issues).
     * Filters are optional and composable: `district_id`, `department`,
     * `category_id`, `status`, `assigned_officer_id`, `unassigned`, `mine`, and
     * `visibility` may be combined to narrow the scoped
     * result set (AND semantics). The `mine` filter (`mine=1` or equivalent truthy
     * query values) restricts active users to issues they own (`issues.user_id`);
     * officers and managers cannot use `mine` and receive 422. The `visibility`
     * filter (`visible` or `hidden`) narrows officer/manager lists to one visibility
     * value; when omitted they see all issues. Users cannot use `visibility` and
     * receive 422. The `status` filter accepts any `IssueStatus` enum value and
     * is available to all active actors. The `assigned_officer_id` and `unassigned`
     * filters are available to officers and managers only; users receive 422.
     * `assigned_officer_id` and `unassigned` are mutually exclusive (422 when both
     * are set). The `department`
     * filter accepts a real department code and
     * is applied against the issue departments relationship with any-match
     * semantics. Pagination is bounded so `per_page` can never exceed a safe
     * maximum.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'district_id' => ['sometimes', 'integer', Rule::exists('districts', 'id')],
            'department' => ['sometimes', 'string', Rule::exists('departments', 'code')],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')],
            'status' => ['sometimes', Rule::enum(IssueStatus::class)],
            'assigned_officer_id' => ['sometimes', 'integer', Rule::exists('officers', 'id')],
            'unassigned' => ['sometimes', Rule::in(['1', 'true', true, 1])],
            'mine' => ['sometimes', Rule::in(['1', 'true', true, 1])],
            'visibility' => ['sometimes', Rule::enum(Visibility::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /**
     * Whether the client requested only issues owned by the authenticated user.
     */
    public function wantsMine(): bool
    {
        if (! $this->filled('mine')) {
            return false;
        }

        return in_array($this->input('mine'), ['1', 'true', true, 1], true);
    }

    /**
     * Whether the client requested only issues with no assigned officer.
     */
    public function wantsUnassigned(): bool
    {
        if (! $this->filled('unassigned')) {
            return false;
        }

        return in_array($this->input('unassigned'), ['1', 'true', true, 1], true);
    }

    /**
     * Reject role-incompatible list filters: `mine` for officers/managers;
     * `visibility`, `assigned_officer_id`, and `unassigned` for users; and
     * mutually exclusive assignee filters.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actor = $this->user();
            $isOfficerOrManager = $actor instanceof Officer || $actor instanceof Manager;

            if ($this->wantsMine() && $isOfficerOrManager) {
                $validator->errors()->add(
                    'mine',
                    'The mine filter is only available to users.',
                );
            }

            if ($this->filled('visibility') && ! $isOfficerOrManager) {
                $validator->errors()->add(
                    'visibility',
                    'The visibility filter is only available to officers and managers.',
                );
            }

            if ($this->filled('assigned_officer_id') && ! $isOfficerOrManager) {
                $validator->errors()->add(
                    'assigned_officer_id',
                    'The assigned officer id filter is only available to officers and managers.',
                );
            }

            if ($this->wantsUnassigned() && ! $isOfficerOrManager) {
                $validator->errors()->add(
                    'unassigned',
                    'The unassigned filter is only available to officers and managers.',
                );
            }

            if ($this->filled('assigned_officer_id') && $this->wantsUnassigned()) {
                $validator->errors()->add(
                    'assigned_officer_id',
                    'The assigned officer id filter cannot be used together with the unassigned filter.',
                );

                $validator->errors()->add(
                    'unassigned',
                    'The unassigned filter cannot be used together with the assigned officer id filter.',
                );
            }
        });
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
