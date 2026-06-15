<?php

namespace App\Http\Requests\Officers;

use Illuminate\Foundation\Http\FormRequest;

class ShowOfficerRequest extends FormRequest
{
    /**
     * Showing an officer is available to any authenticated active actor; route
     * middleware enforces authentication and active status.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
