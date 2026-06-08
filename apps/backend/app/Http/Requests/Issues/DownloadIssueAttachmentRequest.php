<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DownloadIssueAttachmentRequest extends FormRequest
{
    /**
     * Stream downloads are allowed for the issue owner or any active officer or
     * manager.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and may
     * be a User, Officer, or Manager. Active users may download only when
     * `issues.user_id` matches their id (issue owner). Active officers and
     * active managers may download any issue attachment regardless of ownership
     * or assignment. Inactive actors, unauthenticated requests, and non-owner
     * users are rejected with a 403 response. Whether the attachment belongs to
     * the route issue is enforced in the controller, which returns a 404 on a
     * mismatch.
     */
    public function authorize(): bool
    {
        $actor = $this->user();
        $issue = $this->route('issue');

        if (! $issue instanceof Issue) {
            return false;
        }

        if ($actor instanceof User
            && (bool) $actor->is_active === true
            && $issue->user_id === $actor->getKey()) {
            return true;
        }

        if ($actor instanceof Officer && (bool) $actor->is_active === true) {
            return true;
        }

        return $actor instanceof Manager && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
