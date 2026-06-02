<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreManagerRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may create managers.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Creation is restricted to a Manager
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
     * Validation rules for main-manager manager creation.
     *
     * The username is unique against the raw `managers` table column so that
     * soft-deleted rows still reserve their identifiers. The email must be
     * unique across all actor tables (users, officers, managers) so that
     * shared login credentials cannot become ambiguous. Privileged and
     * system-managed fields (`is_main_manager`, `created_by_manager_id`,
     * `is_active`, `remember_token`, `deleted_at`, and tokens) are never
     * accepted from the client; the controller assigns them explicitly.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('managers', 'username')],
            'email' => ['required', 'email', new UniqueActorEmail()],
            'password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'string', 'same:password'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'district_id' => ['nullable', Rule::exists('districts', 'id')],
        ];
    }

    public function username(): string
    {
        return (string) $this->input('username');
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }

    public function confirmPassword(): string
    {
        return (string) $this->input('confirm_password');
    }

    public function departmentId(): int
    {
        return (int) $this->input('department_id');
    }

    public function districtId(): ?int
    {
        $districtId = $this->input('district_id');

        return $districtId === null ? null : (int) $districtId;
    }
}
