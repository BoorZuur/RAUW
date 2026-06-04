<?php

namespace App\Http\Requests\Departments;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DisableDepartmentRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may disable departments.
     *
     * Mirrors UpdateDepartmentRequest: users, officers, non-main managers, and
     * inactive managers are all rejected with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true
            && (bool) $actor->is_main_manager === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
