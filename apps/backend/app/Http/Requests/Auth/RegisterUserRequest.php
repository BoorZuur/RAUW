<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'email', new UniqueActorEmail()],
            'password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'string', 'same:password'],
        ];
    }

    public function name(): string
    {
        return (string) $this->input('name');
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
