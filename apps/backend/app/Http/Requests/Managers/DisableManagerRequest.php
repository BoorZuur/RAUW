<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DisableManagerRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may disable ordinary managers.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Disabling is restricted to a Manager
     * whose `is_main_manager` flag is false (enforced by route binding), so
     * users, officers, non-main managers, and inactive managers are all rejected
     * with a 403 response.
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
        return [];
    }

    /**
     * The route-bound ordinary manager being disabled.
     */
    public function targetManager(): Manager
    {
        /** @var Manager $manager */
        $manager = $this->route('manager');

        return $manager;
    }
}
