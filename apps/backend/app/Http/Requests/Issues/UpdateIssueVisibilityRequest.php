<?php

namespace App\Http\Requests\Issues;

use App\Enums\Visibility;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueVisibilityRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer or manager may change issue visibility.
     *
     * Users, inactive officers, inactive managers, and unauthenticated requests
     * are rejected with a 403 response. Whether the target issue is within the
     * actor's visibility scope is enforced in the controller via
     * IssueVisibilityQuery::canViewIssue(), which returns 404 when the issue
     * cannot be viewed.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

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
        return [
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }
}
