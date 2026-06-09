<?php

namespace App\Http\Resources;

use App\Models\Officer;
use App\Models\OfficerIssueUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OfficerIssueUpdate
 */
class OfficerIssueUpdateResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OfficerIssueUpdate $update */
        $update = $this->resource;

        return [
            'id' => $update->id,
            'issue_id' => $update->issue_id,
            'officer_id' => $update->officer_id,
            'title' => $update->title,
            'content' => $update->content,
            'officer' => $this->compactOfficer($update),
            'attachments' => $this->compactAttachments($update),
            'created_at' => $update->created_at,
            'updated_at' => $update->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactOfficer(OfficerIssueUpdate $update): ?array
    {
        if (! $update->relationLoaded('officer')) {
            return null;
        }

        $officer = $update->getRelation('officer');

        if (! $officer instanceof Officer) {
            return null;
        }

        return [
            'id' => $officer->id,
            'username' => $officer->username,
        ];
    }

    /**
     * @return array<int, mixed>|null
     */
    protected function compactAttachments(OfficerIssueUpdate $update): ?array
    {
        if (! $update->relationLoaded('attachments')) {
            return null;
        }

        return $update->getRelation('attachments')
            ->map(fn ($attachment): array => (new OfficerIssueUpdateAttachmentResource(
                $attachment,
                $update->issue_id,
            ))->resolve())
            ->values()
            ->all();
    }
}
