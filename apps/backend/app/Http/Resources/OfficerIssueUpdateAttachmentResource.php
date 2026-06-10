<?php

namespace App\Http\Resources;

use App\Models\OfficerIssueUpdateAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OfficerIssueUpdateAttachment
 */
class OfficerIssueUpdateAttachmentResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        $resource,
        protected ?int $issueId = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OfficerIssueUpdateAttachment $attachment */
        $attachment = $this->resource;

        return [
            'id' => $attachment->id,
            'issue_id' => $this->issueId,
            'original_name' => $attachment->original_name,
            'file_type' => $attachment->file_type,
            'file_size' => $attachment->file_size,
            'uploaded_at' => $attachment->uploaded_at,
            'download_url' => $this->downloadUrl($attachment),
        ];
    }

    protected function downloadUrl(OfficerIssueUpdateAttachment $attachment): ?string
    {
        if ($this->issueId === null) {
            return null;
        }

        return url("/api/issues/{$this->issueId}/officer-updates/attachments/{$attachment->id}/download");
    }
}
