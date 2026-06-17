<?php

namespace App\Http\Resources;

use App\Models\IssueMessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueMessageAttachment
 */
class IssueMessageAttachmentResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        $resource,
        protected ?int $issueId = null,
        protected ?int $chatId = null,
        protected ?int $messageId = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueMessageAttachment $attachment */
        $attachment = $this->resource;

        return [
            'id' => $attachment->id,
            'issue_message_id' => $attachment->issue_message_id,
            'original_name' => $attachment->original_name,
            'file_type' => $attachment->file_type,
            'file_size' => $attachment->file_size,
            'uploaded_at' => $attachment->uploaded_at,
            'download_url' => $this->downloadUrl($attachment),
        ];
    }

    protected function downloadUrl(IssueMessageAttachment $attachment): ?string
    {
        if ($this->issueId === null || $this->chatId === null || $this->messageId === null) {
            return null;
        }

        return url(
            "/api/issues/{$this->issueId}/chats/{$this->chatId}/messages/{$this->messageId}/attachments/{$attachment->id}/download",
        );
    }
}
