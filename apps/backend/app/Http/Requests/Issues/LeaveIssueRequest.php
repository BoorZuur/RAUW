<?php

namespace App\Http\Requests\Issues;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class LeaveIssueRequest extends FormRequest
{
    /**
     * Only an authenticated, active regular user may leave issue participation.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
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
