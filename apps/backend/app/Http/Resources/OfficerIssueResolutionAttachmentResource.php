<?php

namespace App\Http\Resources;

use App\Models\OfficerIssueResolutionAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an OfficerIssueResolutionAttachment into a payload for API responses.
 *
 * Attachments are stored on the local disk for development and served through
 * an authenticated download endpoint rather than a public storage URL, so the
 * raw `file_path`/`file_url` are never exposed.
 *
 * @mixin OfficerIssueResolutionAttachment
 */
class OfficerIssueResolutionAttachmentResource extends JsonResource
{
    /**
     * @var string|null
     */
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
        /** @var OfficerIssueResolutionAttachment $attachment */
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

    protected function downloadUrl(OfficerIssueResolutionAttachment $attachment): ?string
    {
        if ($this->issueId === null) {
            return null;
        }

        return url("/api/issues/{$this->issueId}/officer-resolution/attachments/{$attachment->id}/download");
    }
}
