<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use App\Support\IssueVisibilityQuery;
use Illuminate\Foundation\Http\FormRequest;

class DownloadOfficerIssueResolutionAttachmentRequest extends FormRequest
{
    /**
     * Stream downloads use visibility-only authorization (Q8 / D15-A): any actor
     * who may view the parent issue may download its resolution attachments.
     *
     * IssueVisibilityQuery::canViewIssue() mirrors show/index scoping — active users
     * may download when the issue is visible or they own it; active officers and
     * managers may download any issue attachment. Matches
     * DownloadIssueAttachmentRequest (same visibility-only rules).
     *
     * A user probing a hidden issue they do not own receives 404 (no enumeration).
     * Other unauthorized actors receive 403 with a message. Whether the attachment
     * belongs to the issue's resolution is enforced in the controller, which
     * returns 404 on a mismatch.
     */
    public function authorize(): bool
    {
        $actor = $this->user();
        $issue = $this->route('issue');

        if (! $issue instanceof Issue) {
            return false;
        }

        if (IssueVisibilityQuery::canViewIssue($issue, $actor)) {
            return true;
        }

        if ($actor instanceof User) {
            abort(404);
        }

        return false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
