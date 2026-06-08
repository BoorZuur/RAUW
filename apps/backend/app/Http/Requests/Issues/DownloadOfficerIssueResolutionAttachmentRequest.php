<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;

class DownloadOfficerIssueResolutionAttachmentRequest extends FormRequest
{
    /**
     * Downloading a resolution attachment is available to any authenticated
     * actor; route middleware enforces authentication and inactive actors are
     * blocked globally. Whether the actor may view the parent issue is enforced
     * in the controller via IssueVisibilityQuery (404 when not visible).
     * Attachment ownership against the issue's resolution is also verified in
     * the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
