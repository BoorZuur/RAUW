<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterOfficerRequest extends FormRequest
{
    private const REGISTRATION_FAILURE_MESSAGE = 'The provided credentials could not be registered.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for officer self-registration.
     *
     * Uniqueness is checked against the raw `officers` table columns so that
     * soft-deleted rows still reserve their identifiers, matching the
     * schema-level unique indexes on username, email, and badge_number.
     * The email must additionally be unique across all actor tables so that
     * officer registration cannot create shared-login ambiguity with users
     * or managers.
     *
     * Each officer must be assigned at least one existing active department through
     * `department_ids`; inactive departments are rejected. `distinct` prevents
     * duplicate pivot assignments.
     *
     * District assignment is optional at registration: `district_ids` may be
     * omitted or empty, but when present every entry must reference an existing
     * active district, with `distinct` preventing duplicate pivot assignments.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('officers', 'username')],
            'email' => ['required', 'email', new UniqueActorEmail(failureMessage: self::REGISTRATION_FAILURE_MESSAGE)],
            'password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'string', 'same:password'],
            'badge_number' => ['required', 'string', 'max:20', Rule::unique('officers', 'badge_number')],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'distinct', Rule::exists('departments', 'id')->where('is_active', true)],
            'district_ids' => ['sometimes', 'array'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('districts', 'id')->where('is_active', true)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => self::REGISTRATION_FAILURE_MESSAGE,
            'badge_number.unique' => self::REGISTRATION_FAILURE_MESSAGE,
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

    public function badgeNumber(): string
    {
        return (string) $this->input('badge_number');
    }

    /**
     * The validated, de-duplicated department IDs to assign to the officer.
     *
     * @return array<int, int>
     */
    public function departmentIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('department_ids', []))));
    }

    /**
     * The validated, de-duplicated district IDs to assign to the officer.
     *
     * Returns an empty array when no districts are provided, allowing officers
     * to register with no district assignments.
     *
     * @return array<int, int>
     */
    public function districtIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('district_ids', []))));
    }

    public function latitude(): float
    {
        return (float) $this->input('latitude');
    }

    public function longitude(): float
    {
        return (float) $this->input('longitude');
    }
}
