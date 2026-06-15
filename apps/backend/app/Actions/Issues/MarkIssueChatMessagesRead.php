<?php

namespace App\Actions\Issues;

use App\Enums\ChatStatus;
use App\Enums\IssueMessageSenderType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssueChatConflict;
use App\Support\Issues\IssueChatAccess;
use Illuminate\Database\Eloquent\Model;

class MarkIssueChatMessagesRead
{
    /**
     * Mark the other party's unread messages as read. Requires an open chat.
     *
     * @return int Number of messages updated
     */
    public function mark(Model $actor, Issue $canonical, IssueChat $chat): int
    {
        IssueChatAccess::assertCanViewChat($actor, $chat, $canonical);

        if ($chat->status !== ChatStatus::Open) {
            throw IssueChatConflict::chatClosed(
                'Cannot mark messages read while the chat is closed.',
            );
        }

        $recipientSenderType = $actor instanceof Officer
            ? IssueMessageSenderType::User
            : IssueMessageSenderType::Officer;

        return IssueMessage::query()
            ->where('issue_chat_id', $chat->getKey())
            ->where('sender_type', $recipientSenderType)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
