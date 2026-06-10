<?php

namespace App\Http\Requests\Officers;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class EndOfficerShiftRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may end an officer's shared shift.
     *
     * Users, officers, and inactive managers are rejected with 403. Hub scoping
     * is enforced in the controller via ManagerOfficerHubAccess; hub mismatch
     * or null hub returns 404.
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
