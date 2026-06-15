<?php

namespace App\Http\Requests\Issues;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class MarkIssueAsDuplicateRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer or manager may link an existing issue
     * as a duplicate of a canonical target.
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
            'duplicate_of_id' => ['required', 'integer', 'exists:issues,id'],
        ];
    }
}
