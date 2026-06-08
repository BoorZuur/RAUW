<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteIssueAttachmentRequest extends FormRequest
{
    /**
     * Only the authenticated, active regular user who owns the target issue may
     * delete one of its attachments.
     *
     * Mirrors DeleteIssueRequest/StoreIssueAttachmentRequest: officers, managers,
     * inactive users, unauthenticated requests, and any non-owner user are all
     * rejected with a 403 response. Ownership is asserted against
     * `issues.user_id`, which is retained even for anonymous reports so the
     * author can still manage their own report's attachments. Whether the
     * attachment actually belongs to the route issue is enforced in the
     * controller, which returns a 404 on a mismatch.
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
