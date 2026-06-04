<?php

namespace App\Http\Requests\Districts;

use App\Models\District;
use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update districts.
     *
     * Mirrors StoreDistrictRequest: users, officers, non-main managers, and
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
     * Validation rules for district updates.
     *
     * All fields use `sometimes` so a partial update only validates and applies
     * the provided keys. The `name` uniqueness check ignores the district being
     * updated so re-submitting its own name is not flagged as a conflict.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $district = $this->route('district');
        $districtId = $district instanceof District ? $district->getKey() : null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('districts', 'name')->ignore($districtId),
            ],
            'postal_prefix' => ['sometimes', 'nullable', 'string', 'max:10'],
            'center_lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
