<?php

namespace App\Http\Requests\Issues;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class DeleteOfficerIssueResolutionAttachmentRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may delete a resolution attachment.
     *
     * Mirrors StoreOfficerIssueResolutionRequest/UpdateOfficerIssueResolutionRequest:
     * users, managers, inactive officers, and unauthenticated requests are rejected
     * with 403. District access, assignee checks, and attachment ownership are
     * enforced in the controller on the locked issue row.
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
        return [];
    }
}
