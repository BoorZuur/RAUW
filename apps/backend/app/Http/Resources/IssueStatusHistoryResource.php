<?php

namespace App\Http\Resources;

use App\Enums\IssueStatus;
use App\Models\IssueStatusHistory;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes one issue status history row for officer and manager endpoints.
 *
 * Does not include officer GPS coordinates. The compact `officer` summary is
 * included only when `changedByOfficer` has been eager loaded.
 *
 * @mixin IssueStatusHistory
 */
class IssueStatusHistoryResource extends JsonResource
{
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
            'old_status' => $this->statusValue($history->old_status),
            'new_status' => $this->statusValue($history->new_status),
            'note' => $history->note,
            'changed_at' => $history->changed_at,
            'officer' => $this->compactOfficer($history),
        ];
    }

    protected function statusValue(IssueStatus|string|null $status): ?string
    {
        if ($status instanceof IssueStatus) {
            return $status->value;
        }

        return $status;
    }

    /**
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
