<?php

namespace App\Http\Requests\Hubs;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates hub creation.
 *
 * New hubs are inactive by default until activated via `is_active: true` on
 * create or a follow-up PATCH.
 */
class StoreHubRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may create hubs.
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
            'name' => ['required', 'string', 'max:100', Rule::unique('hubs', 'name')],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'radius_meters' => ['sometimes', 'integer', 'min:10', 'max:5000'],
        ];
    }
}
