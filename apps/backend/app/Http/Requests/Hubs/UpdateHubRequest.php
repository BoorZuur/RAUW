<?php

namespace App\Http\Requests\Hubs;

use App\Models\Hub;
use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHubRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update hubs.
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
        $hub = $this->route('hub');
        $hubId = $hub instanceof Hub ? $hub->getKey() : null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('hubs', 'name')->ignore($hubId),
            ],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:10'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
