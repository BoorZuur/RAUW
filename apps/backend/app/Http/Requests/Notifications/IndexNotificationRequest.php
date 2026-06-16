<?php

namespace App\Http\Requests\Notifications;

use App\Enums\NotificationType;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationRequest extends FormRequest
{
    /**
     * The default number of notifications returned per page when `per_page` is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'is_read' => ['sometimes', 'boolean'],
            'type' => ['sometimes', Rule::enum(NotificationType::class)],
            'since' => ['sometimes', 'date'],
        ];
    }

    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }

    private function isOfficerRoute(): bool
    {
        $name = $this->route()?->getName() ?? '';

        return str_starts_with($name, 'officers.me.notifications');
    }
}
