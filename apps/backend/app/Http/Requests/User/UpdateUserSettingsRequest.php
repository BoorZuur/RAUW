<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingsRequest extends FormRequest
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
        return [
            'notify_status_changes' => ['sometimes', 'boolean'],
            'notify_district_news' => ['sometimes', 'boolean'],
        ];
    }
}
