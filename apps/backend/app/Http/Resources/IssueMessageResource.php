<?php

namespace App\Http\Resources;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Models\Issue;
use App\Models\IssueMessage;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueChatAliasResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueMessage
 */
class IssueMessageResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        $resource,
        protected ?Issue $canonicalIssue = null,
        protected ?int $chatId = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueMessage $message */
        $message = $this->resource;

        return [
            'id' => $message->id,
            'issue_chat_id' => $message->issue_chat_id,
            'issue_id' => $message->issue_id,
            'message_type' => $message->message_type->value,
            'sender_type' => $message->sender_type->value,
            'content' => $message->content,
            'meta' => $message->message_type === IssueMessageType::System ? $message->meta : null,
            'is_read' => (bool) $message->is_read,
            'sender' => $this->compactSender($message),
            'attachments' => $this->compactAttachments($message),
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactSender(IssueMessage $message): ?array
    {
        if ($message->sender_type === IssueMessageSenderType::System) {
            return [
                'type' => IssueMessageSenderType::System->value,
            ];
        }

        if ($message->sender_type === IssueMessageSenderType::Officer) {
            if ($message->relationLoaded('officer')) {
                $officer = $message->getRelation('officer');

                if ($officer instanceof Officer) {
                    return [
                        'type' => IssueMessageSenderType::Officer->value,
                        'id' => $officer->id,
                        'username' => $officer->username,
                        'display_name' => $officer->username,
                    ];
                }
            }

            return [
                'type' => IssueMessageSenderType::Officer->value,
                'id' => $message->officer_id,
            ];
        }

        if ($message->sender_type === IssueMessageSenderType::User) {
            if ($message->relationLoaded('user') && $this->canonicalIssue !== null) {
                $user = $message->getRelation('user');

                if ($user instanceof User) {
                    return array_merge(
                        ['type' => IssueMessageSenderType::User->value],
                        (new IssueChatAliasResolver)->forUserOnIssue($user, $this->canonicalIssue),
                    );
                }
            }

            return [
                'type' => IssueMessageSenderType::User->value,
                'id' => $message->user_id,
            ];
        }

        return null;
    }

    /**
     * @return array<int, mixed>|null
     */
    protected function compactAttachments(IssueMessage $message): ?array
    {
        if (! $message->relationLoaded('attachments')) {
            return null;
        }

        return $message->getRelation('attachments')
            ->map(fn ($attachment): array => (new IssueMessageAttachmentResource(
                $attachment,
                $message->issue_id,
                $this->chatId ?? $message->issue_chat_id,
                $message->id,
            ))->resolve())
            ->values()
            ->all();
    }
}
