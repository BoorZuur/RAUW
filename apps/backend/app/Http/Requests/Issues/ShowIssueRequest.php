<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;

class ShowIssueRequest extends FormRequest
{
    /**
     * Showing an issue is available to any authenticated actor; route middleware
     * enforces authentication and inactive actors are blocked globally. Visibility,
     * ownership, and district assignment are enforced in the controller via
     * IssueVisibilityQuery, which returns 404 when the issue is not visible to
     * the actor. Officers and ordinary managers may only view issues in assigned
     * districts; main managers may view any issue city-wide.
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
