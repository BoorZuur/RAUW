<?php

namespace App\Http\Requests\Hubs;

use Illuminate\Foundation\Http\FormRequest;

class ShowHubRequest extends FormRequest
{
    /**
     * Showing a hub is available to any authenticated active actor; route
     * middleware enforces authentication.
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
