<?php

namespace App\Http\Requests\Issues;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    /**
     * Only an authenticated, active regular user may create issues.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and may
     * be a User, Officer, or Manager. Issue creation is a citizen-facing action,
     * so it is restricted to a User whose `is_active` flag is true. Officers,
     * managers, inactive users, and unsupported/unauthenticated actors are all
     * rejected with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for issue creation.
     *
     * Required reporting fields are `title`, `content`, `category_id`, and
     * `district_id`. The `category_id` and `district_id` must reference
     * existing active records. The issue's departments are derived
     * server-side from the selected category's department assignments and are
     * never accepted from the client. Location metadata (`postal_code`,
     * `address`, `latitude`, `longitude`) is optional. The optional
     * `is_anonymous` flag opts the report into anonymous display; the stable
     * `anonymous_alias` is generated server-side and is never accepted from the
     * client. The integer `priority` is derived server-side from the selected
     * category's main-category priority and is prohibited in the request.
     * Triage-owned fields (status, assignment, counters, visibility) are
     * intentionally not accepted here. Optional `duplicate_of_id` links the new
     * report as a duplicate child of an existing canonical issue; category
     * mismatch with the canonical is allowed.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('is_active', true)],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'duplicate_of_id' => ['nullable', 'integer', Rule::exists('issues', 'id')],
            'priority' => ['prohibited'],
            'status' => ['prohibited'],
            'visibility' => ['prohibited'],
            'assigned_officer_id' => ['prohibited'],
            'duplicate_count' => ['prohibited'],
            'participant_count' => ['prohibited'],
        ];
    }
}
