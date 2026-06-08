<?php

namespace App\Http\Requests\OfficerSessions;

use App\Models\Manager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOfficerSessionRequest extends FormRequest
{
    /**
     * The default number of sessions returned per page when `per_page` is omitted.
     */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * The safe upper bound for `per_page` to protect the list endpoint from
     * unbounded result sets.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Only an authenticated, active manager may list officer sessions.
     *
     * Users, officers, and inactive managers receive a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for officer session list filters and pagination.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'officer_id' => ['sometimes', 'integer', Rule::exists('officers', 'id')],
            'hub_id' => ['sometimes', 'integer', Rule::exists('hubs', 'id')],
            'is_hub_active' => ['sometimes', Rule::in(['0', 'false', false, 0, '1', 'true', true, 1])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /**
     * Whether the client requested only hub-active sessions.
     *
     * When `is_hub_active` is omitted, no hub-active filter is applied.
     */
    public function wantsHubActiveSessions(): ?bool
    {
        if (! $this->filled('is_hub_active')) {
            return null;
        }

        return in_array($this->input('is_hub_active'), ['1', 'true', true, 1], true);
    }

    /**
     * The validated, bounded number of sessions to return per page.
     */
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
