<?php

namespace App\Http\Requests\Hubs;

use App\Models\Hub;
use App\Models\Manager;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates hub updates.
 *
 * Deactivation is performed via PATCH with `is_active: false`; there is no
 * dedicated disable route. Deactivation is blocked while active districts or
 * active officers remain assigned to the hub.
 */
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
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'radius_meters' => ['sometimes', 'integer', 'min:10', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('is_active')) {
                return;
            }

            $hub = $this->route('hub');

            if (! $hub instanceof Hub) {
                return;
            }

            if ($this->boolean('is_active') || ! $hub->is_active) {
                return;
            }

            if ($hub->districts()->where('is_active', true)->exists()) {
                $validator->errors()->add(
                    'is_active',
                    'Cannot deactivate hub while active districts are assigned.',
                );
            }

            if ($hub->officers()->where('is_active', true)->exists()) {
                $validator->errors()->add(
                    'is_active',
                    'Cannot deactivate hub while active officers are assigned.',
                );
            }
        });
    }
}
