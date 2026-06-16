<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use Illuminate\Support\Collection;

class NotifyChatClosed
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, IssueChat $chat, Officer $officer): void
    {
        $this->forChats($issue, collect([$chat]), $officer);
    }

    /**
     * @param  Collection<int, IssueChat>  $chats
     */
    public function forChats(Issue $issue, Collection $chats, Officer $officer): void
    {
        if ($chats->isEmpty()) {
            return;
        }

        $canonical = ($this->resolveCanonicalIssue)($issue);
        $copy = NotificationTemplates::chatClosed($canonical->title, $officer->username);

        $inserts = $chats->map(static function (IssueChat $chat) use ($canonical, $officer, $copy): NotificationInsert {
            return new NotificationInsert(
                recipientType: ActorType::User,
                type: NotificationType::ChatClosed,
                title: $copy['title'],
                userId: (int) $chat->user_id,
                issueId: (int) $canonical->getKey(),
                body: $copy['body'],
                payload: ['chat_id' => (int) $chat->getKey()],
                dedupKey: NotificationDeduplicator::chatClosed(
                    (int) $canonical->getKey(),
                    (int) $chat->getKey(),
                    (int) $chat->user_id,
                ),
                actorType: ActorType::Officer,
                actorId: (int) $officer->getKey(),
            );
        });

        $filtered = $this->writer->excludeActor($inserts, $officer);

        $this->writer->writeMany($filtered);
    }
}
