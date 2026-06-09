<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Officers must send device coordinates on login; users and managers ignore them.
     *
     * @return array<string, array<int, string>>
     */
    public function officerCoordinateRules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function validateOfficerCoordinates(): void
    {
        $this->validate($this->officerCoordinateRules());
    }

    public function latitude(): ?float
    {
        return $this->has('latitude') ? (float) $this->input('latitude') : null;
    }

    public function longitude(): ?float
    {
        return $this->has('longitude') ? (float) $this->input('longitude') : null;
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }
}
