<?php

namespace App\Http\Requests\Officers;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DisableOfficerRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may disable an officer.
     *
     * Users, officers, and inactive managers are rejected with 403. Hub scoping
     * (ordinary managers may only administer officers in the same hub; main
     * managers are city-wide) is enforced in the controller via
     * ManagerOfficerHubAccess; hub mismatch or null hub returns 404.
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
