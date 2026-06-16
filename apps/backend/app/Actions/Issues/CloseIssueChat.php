<?php

namespace App\Actions\Issues;

use App\Enums\ChatStatus;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Support\IssueChatConflict;
use App\Support\Notifications\NotifyChatClosed;
use App\Support\OfficerIssueRowLock;

class CloseIssueChat
{
    public function __construct(
        private readonly InsertIssueChatSystemMessage $insertSystemMessage,
        private readonly NotifyChatClosed $notifyChatClosed = new NotifyChatClosed,
    ) {}

    /**
     * Close a single chat. Optionally insert a system message after closing.
     */
    public function close(
        Officer $officer,
        Issue $canonical,
        IssueChat $chat,
        ?string $systemCode = null,
    ): IssueChat {
        return OfficerIssueRowLock::withLockedIssue($canonical, function (Issue $locked) use ($officer, $chat, $systemCode): IssueChat {
            OfficerIssueRowLock::assertAssignee(
                $officer,
                $locked,
                'Only the assigned officer may close a chat on this issue.',
            );

            if ($chat->issue_id !== $locked->getKey()) {
                throw IssueChatConflict::chatNotFound();
            }

            if ($chat->status === ChatStatus::Closed) {
                return $chat;
            }

            $chat->update([
                'status' => ChatStatus::Closed,
                'closed_by_officer_id' => $officer->getKey(),
            ]);

            if ($systemCode !== null) {
                $this->insertSystemMessage->insert($chat, $locked, $systemCode);
            }

            $chat->refresh();

            $this->notifyChatClosed->notify($locked, $chat, $officer);

            return $chat;
        });
    }
}
