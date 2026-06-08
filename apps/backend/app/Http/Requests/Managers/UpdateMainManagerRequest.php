<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateMainManagerRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may update main managers.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Updates are restricted to a Manager
     * whose `is_active` and `is_main_manager` flags are both true, so users,
     * officers, non-main managers, and inactive managers are all rejected
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
     * Partial PATCH validation for main-manager identity updates.
     *
     * Username uniqueness is checked against the managers table so that
     * soft-deleted rows still reserve their identifiers. Email must remain
     * unique across all actor tables, ignoring the target manager's row when
     * checking the managers table. Privileged and system-managed fields
     * (`is_main_manager`, `is_active`, `created_by_manager_id`, department
     * and district assignments, and tokens) are never accepted from the client.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $manager = $this->targetManager();

        return [
            'username' => ['sometimes', 'string', 'max:50', Rule::unique('managers', 'username')->ignore($manager->id)],
            'email' => ['sometimes', 'email', new UniqueActorEmail('managers', $manager->id)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'confirm_password' => ['required_with:password', 'string', 'same:password'],
            'department_ids' => ['prohibited'],
            'district_ids' => ['prohibited'],
            'is_main_manager' => ['prohibited'],
            'is_active' => ['prohibited'],
            'created_by_manager_id' => ['prohibited'],
        ];
    }

    /**
     * Validated model attributes to persist, excluding confirm_password.
     *
     * Only keys present in the request and that passed validation are returned.
     * Password is returned in plain text so the model's hashed cast can apply.
     *
     * @return array<string, mixed>
     */
    public function changedAttributes(): array
    {
        return collect($this->validated())
            ->except('confirm_password')
            ->all();
    }

    /**
     * The route-bound main manager being updated.
     */
    public function targetManager(): Manager
    {
        /** @var Manager $manager */
        $manager = $this->route('manager');

        return $manager;
    }
}
