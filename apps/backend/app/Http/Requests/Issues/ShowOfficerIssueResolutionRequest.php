<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;

class ShowOfficerIssueResolutionRequest extends FormRequest
{
    /**
     * Showing a resolution is available to any authenticated actor; route
     * middleware enforces authentication and inactive actors are blocked
     * globally. Visibility is enforced in the controller via
     * IssueVisibilityQuery, which returns 404 when the issue is not visible.
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
