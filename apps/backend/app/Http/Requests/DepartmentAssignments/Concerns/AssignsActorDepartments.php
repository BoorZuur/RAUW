<?php

namespace App\Http\Requests\DepartmentAssignments\Concerns;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared authorization and validation for main-manager actor department sync.
 *
 * @mixin FormRequest
 */
trait AssignsActorDepartments
{
    /**
     * Only an authenticated, active main manager may sync actor department pivots.
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
        return [
            'department_ids' => ['present', 'array'],
            'department_ids.*' => ['integer', 'distinct', Rule::exists('departments', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function departmentIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('department_ids', []))));
    }
}
