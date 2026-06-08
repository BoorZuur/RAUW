<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueStatus;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueStatusRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may update issue status.
     *
     * Users, managers, inactive officers, and unauthenticated requests are
     * rejected with a 403 response. District access, assignee checks, and
     * transition validation run on the locked issue row in the controller.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IssueStatus::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
