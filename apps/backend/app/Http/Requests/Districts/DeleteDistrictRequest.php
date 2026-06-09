<?php

namespace App\Http\Requests\Districts;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DeleteDistrictRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may delete districts.
     *
     * Users, officers, non-main managers, and inactive managers are all rejected
     * with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true
            && (bool) $actor->is_main_manager === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
