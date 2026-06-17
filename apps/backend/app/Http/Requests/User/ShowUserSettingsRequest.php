<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ShowUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && (bool) $this->user()->is_active;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
