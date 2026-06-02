<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterOfficerRequest extends FormRequest
{
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
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('officers', 'username')],
            'email' => ['required', 'email', Rule::unique('officers', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'string', 'same:password'],
            'badge_number' => ['required', 'string', 'max:20', Rule::unique('officers', 'badge_number')],
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
}
