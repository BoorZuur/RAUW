<?php

namespace App\Http\Requests\Departments;

use App\Models\Department;
use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update departments.
     *
     * Mirrors StoreDepartmentRequest: users, officers, non-main managers, and
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
     * Validation rules for department updates.
     *
     * All fields use `sometimes` so a partial update only validates and applies
     * the provided keys. Activation and deactivation use `is_active` on this
     * request only; there is no dedicated `/disable` route. The `code` uniqueness
     * check ignores the department currently being updated so re-submitting its
     * own code is not flagged as a conflict.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $department = $this->route('department');
        $departmentId = $department instanceof Department ? $department->getKey() : null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
