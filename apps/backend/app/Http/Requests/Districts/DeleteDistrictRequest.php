<?php

namespace App\Http\Requests\Districts;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DeleteDistrictRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may delete districts.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
