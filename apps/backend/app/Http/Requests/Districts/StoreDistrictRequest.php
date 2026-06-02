<?php

namespace App\Http\Requests\Districts;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may create districts.
     *
     * Users, officers, and inactive managers are all rejected with a 403
     * response. District management is open to ordinary managers and is not
     * limited to main managers.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for district creation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('districts', 'name')],
            'postal_prefix' => ['nullable', 'string', 'max:10'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
