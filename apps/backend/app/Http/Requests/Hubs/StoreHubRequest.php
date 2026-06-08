<?php

namespace App\Http\Requests\Hubs;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
