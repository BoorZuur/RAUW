<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteIssueRequest extends FormRequest
{
    /**
     * Only the authenticated, active regular user who owns the target issue may
     * delete it.
     *
     * Mirrors UpdateIssueRequest: officers, managers, inactive users,
     * unauthenticated requests, and any non-owner user are all rejected with a
     * 403 response. Ownership is asserted against `issues.user_id`, which is
     * retained even for anonymous reports. Deletion uses hard-delete semantics
     * handled by the controller.
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
