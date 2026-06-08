<?php

namespace App\Http\Requests\Officers;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DisableOfficerRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may disable an officer.
     *
     * Users, officers, and inactive managers are all rejected with a 403
     * response. Any active manager (not just a main manager) may deactivate
     * officers by setting is_active to false without soft-deleting the row.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
