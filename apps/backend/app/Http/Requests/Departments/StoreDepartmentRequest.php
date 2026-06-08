<?php

namespace App\Http\Requests\Departments;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may create departments.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Creation is restricted to a Manager
     * whose `is_active` and `is_main_manager` flags are both true, so users,
     * officers, non-main managers, and inactive managers are all rejected with
     * a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true
            && (bool) $actor->is_main_manager === true;
    }

    /**
     * Validation rules for department creation.
     *
     * The `code` is a stable, unique identifier validated against the raw
     * `departments` table column. The `name` is the human-readable display
     * label. The optional `is_active` flag defaults to true via the model when
     * omitted. Only validated keys are ever consumed by the controller.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
