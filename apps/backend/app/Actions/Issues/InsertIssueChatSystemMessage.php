<?php

namespace App\Actions\Issues;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;

class InsertIssueChatSystemMessage
{
    /**
     * @var array<string, array{content: string, meta: array<string, mixed>}>
     */
    private const MESSAGES = [
        'chat_closed_status_gesloten' => [
            'content' => 'Chat gesloten omdat de melding is afgerond.',
            'meta' => ['code' => 'chat_closed_status_gesloten'],
        ],
    ];

    public function insert(IssueChat $chat, Issue $canonical, string $code): IssueMessage
    {
        $template = self::MESSAGES[$code] ?? [
            'content' => $code,
            'meta' => ['code' => $code],
        ];

        return $chat->messages()->create([
            'issue_id' => $canonical->getKey(),
            'message_type' => IssueMessageType::System,
            'sender_type' => IssueMessageSenderType::System,
            'user_id' => null,
            'officer_id' => null,
            'content' => $template['content'],
            'meta' => $template['meta'],
            'is_read' => true,
        ]);
    }
}
