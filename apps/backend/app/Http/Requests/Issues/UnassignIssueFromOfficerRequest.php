<?php

namespace App\Http\Requests\Issues;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class UnassignIssueFromOfficerRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may unassign themselves from an issue.
     *
     * Users, managers, inactive officers, and unauthenticated requests are
     * rejected with a 403 response. Whether the target issue is within the
     * actor's visibility scope and district is enforced in the controller.
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
