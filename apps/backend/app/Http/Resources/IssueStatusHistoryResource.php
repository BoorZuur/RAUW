<?php

namespace App\Http\Resources;

use App\Models\IssueStatusHistory;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an issue status history row for officer and manager issue payloads.
 *
 * @mixin IssueStatusHistory
 */
class IssueStatusHistoryResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueStatusHistory $history */
        $history = $this->resource;

        return [
            'id' => $history->id,
            'issue_id' => $history->issue_id,
            'changed_by_officer_id' => $history->changed_by_officer_id,
            'old_status' => $history->old_status,
            'new_status' => $history->new_status,
            'note' => $history->note,
            'changed_at' => $history->changed_at,
            'officer' => $this->compactOfficer($history),
        ];
    }

    /**
     * Return a compact officer summary only when the relation is loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactOfficer(IssueStatusHistory $history): ?array
    {
        if (! $history->relationLoaded('changedByOfficer')) {
            return null;
        }

        $officer = $history->getRelation('changedByOfficer');

        if (! $officer instanceof Officer) {
            return null;
        }

        return [
            'id' => $officer->id,
            'username' => $officer->username,
        ];
    }
}
