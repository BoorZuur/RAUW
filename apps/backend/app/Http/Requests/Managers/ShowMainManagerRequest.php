<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class ShowMainManagerRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer or manager may view a main manager
     * profile.
     *
     * Users, inactive officers, inactive managers, and unauthenticated requests
     * are rejected with a 403 response.
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
        return [];
    }
}
