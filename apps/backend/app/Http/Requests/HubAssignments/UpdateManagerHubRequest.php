<?php

namespace App\Http\Requests\HubAssignments;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagerHubRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update a manager's hub
     * assignment.
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
            'hub_id' => ['required', 'integer', Rule::exists('hubs', 'id')->where('is_active', true)],
        ];
    }
}
