<?php

namespace App\Http\Requests\Categories;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCategoryRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may delete categories.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Deletion is restricted to a Manager
     * whose `is_active` flag is true, so users, officers, and inactive
     * managers are all rejected with a 403 response. Category management is
     * open to ordinary managers (not just main managers).
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
