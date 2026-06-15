<?php

namespace App\Http\Resources;

use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an OfficerIssueResolution into a payload for API responses.
 *
 * @mixin OfficerIssueResolution
 */
class OfficerIssueResolutionResource extends JsonResource
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
        /** @var OfficerIssueResolution $resolution */
        $resolution = $this->resource;

        return [
            'id' => $resolution->id,
            'issue_id' => $resolution->issue_id,
            'officer_id' => $resolution->officer_id,
            'title' => $resolution->title,
            'content' => $resolution->content,
            'officer' => $this->compactOfficer($resolution),
            'attachments' => $this->compactAttachments($resolution),
            'created_at' => $resolution->created_at,
            'updated_at' => $resolution->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactOfficer(OfficerIssueResolution $resolution): ?array
    {
        if (! $resolution->relationLoaded('officer')) {
            return null;
        }

        $officer = $resolution->getRelation('officer');

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
    protected function compactAttachments(OfficerIssueResolution $resolution): ?array
    {
        if (! $resolution->relationLoaded('attachments')) {
            return null;
        }

        return $resolution->getRelation('attachments')
            ->map(fn ($attachment): array => (new OfficerIssueResolutionAttachmentResource(
                $attachment,
                $resolution->issue_id,
            ))->resolve())
            ->values()
            ->all();
    }
}
