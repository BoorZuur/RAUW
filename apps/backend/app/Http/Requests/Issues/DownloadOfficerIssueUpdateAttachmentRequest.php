<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use App\Models\User;
use App\Support\IssueVisibilityQuery;
use Illuminate\Foundation\Http\FormRequest;

class DownloadOfficerIssueUpdateAttachmentRequest extends FormRequest
{
    /**
     * Stream downloads use visibility-only authorization: any actor
     * who may view the parent issue may download its update attachments.
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
