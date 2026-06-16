<?php

namespace App\Http\Requests\Notifications;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class BulkReadNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if ($this->isOfficerRoute()) {
            return $actor instanceof Officer && (bool) $actor->is_active === true;
        }

        return $actor instanceof User && (bool) $actor->is_active === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }

    private function isOfficerRoute(): bool
    {
        $name = $this->route()?->getName() ?? '';

        return str_starts_with($name, 'officers.me.notifications');
    }
}
