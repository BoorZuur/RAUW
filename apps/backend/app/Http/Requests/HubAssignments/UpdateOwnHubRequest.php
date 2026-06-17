<?php

namespace App\Http\Requests\HubAssignments;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnHubRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may update their own hub.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
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
