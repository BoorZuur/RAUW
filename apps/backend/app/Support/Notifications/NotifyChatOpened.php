<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;

class NotifyChatOpened
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, IssueChat $chat, Officer $officer): void
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        $copy = NotificationTemplates::chatOpened($canonical->title, $officer->username);

        $insert = new NotificationInsert(
            recipientType: ActorType::User,
            type: NotificationType::ChatOpened,
            title: $copy['title'],
            userId: (int) $chat->user_id,
            issueId: (int) $canonical->getKey(),
            body: $copy['body'],
            payload: ['chat_id' => (int) $chat->getKey()],
            dedupKey: NotificationDeduplicator::chatOpened(
                (int) $canonical->getKey(),
                (int) $chat->getKey(),
                (int) $chat->user_id,
            ),
            actorType: ActorType::Officer,
            actorId: (int) $officer->getKey(),
        );

        $filtered = $this->writer->excludeActor(collect([$insert]), $officer);

        $this->writer->writeMany($filtered);
    }
}
