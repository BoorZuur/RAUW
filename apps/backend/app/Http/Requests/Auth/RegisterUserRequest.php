<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    private const REGISTRATION_FAILURE_MESSAGE = 'The provided credentials could not be registered.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for public user self-registration.
     *
     * The username is unique against the raw `users` table column so that
     * soft-deleted rows still reserve their identifiers. The email must be
     * unique across all actor tables (users, officers, managers) so that
     * shared login credentials cannot become ambiguous. System-managed
     * fields (is_active, flag_count, is_under_review, email_verified_at,
     * remember_token, deleted_at, and tokens) are never accepted from the
     * client and are left to the model defaults.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'email' => ['required', 'email', new UniqueActorEmail(failureMessage: self::REGISTRATION_FAILURE_MESSAGE)],
            'password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'string', 'same:password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => self::REGISTRATION_FAILURE_MESSAGE,
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
}
