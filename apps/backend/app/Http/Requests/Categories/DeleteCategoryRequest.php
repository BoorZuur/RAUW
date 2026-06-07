<?php

namespace App\Http\Requests\Categories;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCategoryRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may delete categories.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Deletion is restricted to a Manager
     * whose `is_active` and `is_main_manager` flags are both true, so users,
     * officers, non-main managers, and inactive managers are all rejected with
     * a 403 response.
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
