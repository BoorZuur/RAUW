<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Only the authenticated, active regular user who owns the target issue may
     * update it.
     *
     * The issue is resolved from the route binding. Officers, managers,
     * inactive users, unauthenticated requests, and any user who is not the
     * issue's owner are all rejected with a 403 response. Ownership is asserted
     * against `issues.user_id`, which is retained even for anonymous reports so
     * the author can still manage their own issue.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User || (bool) $actor->is_active !== true) {
            return false;
        }

        $issue = $this->route('issue');

        return $issue instanceof Issue
            && $issue->user_id === $actor->getKey();
    }

    /**
     * Validation rules for issue updates.
     *
     * Only fields the author legitimately owns are editable, and every rule uses
     * `sometimes` so a partial update validates and applies only the supplied
     * keys. Changing `category_id` re-derives the issue's departments and
     * integer `priority` server-side from the new category's main-category
     * priority, so departments and priority are never accepted from the client.
     * Ownership (`user_id`), triage state (`status`, assignment, counters),
     * the server-generated `anonymous_alias`, and the removed `neighborhood`
     * field are intentionally excluded and can never be set through this
     * request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'district_id' => ['sometimes', 'required', 'integer', Rule::exists('districts', 'id')->where('is_active', true)],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'priority' => ['prohibited'],
        ];
    }
}
