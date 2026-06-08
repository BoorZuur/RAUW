<?php

namespace App\Http\Requests\Hubs;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;

class DeleteHubRequest extends FormRequest
{
    /**
     * Only an authenticated, active main manager may delete hubs.
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
