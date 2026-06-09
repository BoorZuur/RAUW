<?php

namespace App\Http\Requests\IssueComments;

use App\Enums\Visibility;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommentVisibilityRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer or manager may change comment visibility.
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
     * Validation rules for updating comment visibility.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }
}
