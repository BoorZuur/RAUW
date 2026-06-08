<?php

namespace App\Http\Requests\Auth;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;

class StartOfficerShiftRequest extends FormRequest
{
    /**
     * Only an authenticated, active officer may start a shared shift.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Officer
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
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
