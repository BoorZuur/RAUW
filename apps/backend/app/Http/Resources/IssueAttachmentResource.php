<?php

namespace App\Http\Resources;

use App\Models\IssueAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an IssueAttachment into a payload for API responses.
 *
 * Attachments are stored on the local disk for development and served through
 * an authenticated download endpoint rather than a public storage URL, so the
 * raw `file_path`/`file_url` are never exposed. The `download_url` points at the
 * authenticated route that streams the file after re-checking access, keeping
 * uploaded files private to authorized actors.
 *
 * @mixin IssueAttachment
 */
class IssueAttachmentResource extends JsonResource
{
    /**
     * Disable wrapping so collections and single resources share a flat shape,
     * consistent with the other API resources in this codebase.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueAttachment $attachment */
        $attachment = $this->resource;

        return [
            'id' => $attachment->id,
            'issue_id' => $attachment->issue_id,
            'original_name' => $attachment->original_name,
            'file_type' => $attachment->file_type,
            'file_size' => $attachment->file_size,
            'uploaded_at' => $attachment->uploaded_at,
            'download_url' => $this->downloadUrl($attachment),
        ];
    }

    /**
     * Build the absolute URL for the authenticated attachment download endpoint.
     *
     * The path is constructed directly (rather than via a named route) so the
     * resource is decoupled from route registration order, while still pointing
     * at the authenticated download route served by the controller.
     */
    protected function downloadUrl(IssueAttachment $attachment): string
    {
        return url("/api/issues/{$attachment->issue_id}/attachments/{$attachment->id}/download");
    }
}
