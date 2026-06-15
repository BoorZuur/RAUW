<?php

namespace App\Actions\Issues;

use App\Enums\ChatStatus;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Support\Notifications\NotifyChatClosed;
use Illuminate\Support\Collection;

class CloseAllOpenIssueChats
{
    public function __construct(
        private readonly InsertIssueChatSystemMessage $insertSystemMessage,
        private readonly NotifyChatClosed $notifyChatClosed = new NotifyChatClosed,
    ) {}

    /**
     * Close every open chat on the locked canonical issue.
     *
     * @return Collection<int, IssueChat>
     */
    public function closeAll(Issue $canonical, Officer $officer, bool $withSystemMessage = false): Collection
    {
        $openChats = IssueChat::query()
            ->where('issue_id', $canonical->getKey())
            ->where('status', ChatStatus::Open)
            ->get();

        foreach ($openChats as $chat) {
            $chat->update([
                'status' => ChatStatus::Closed,
                'closed_by_officer_id' => $officer->getKey(),
            ]);

            if ($withSystemMessage) {
                $this->insertSystemMessage->insert($chat, $canonical, 'chat_closed_status_gesloten');
            }
        }

        $this->notifyChatClosed->forChats($canonical, $openChats, $officer);

        return $openChats;
    }
}
