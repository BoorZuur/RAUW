<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\IssueMessageType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueAnonymousDisplayName;
use Illuminate\Database\Eloquent\Model;

class NotifyNewMessage
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveIssueChatPartner $chatPartner = new ResolveIssueChatPartner,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(IssueMessage $message, IssueChat $chat, Issue $issue, Model $sender): void
    {
        if ($message->message_type !== IssueMessageType::Message) {
            return;
        }

        $canonical = ($this->resolveCanonicalIssue)($issue);
        $partner = $this->chatPartner->resolve($chat, $canonical, $sender);

        if ($partner === null) {
            return;
        }

        $copy = NotificationTemplates::newMessage(
            $canonical->title,
            $this->actorDisplayName($sender, $canonical),
        );

        $insert = new NotificationInsert(
            recipientType: $partner->recipientType,
            type: NotificationType::NewMessage,
            title: $copy['title'],
            userId: $partner->userId,
            officerId: $partner->officerId,
            issueId: (int) $canonical->getKey(),
            body: $copy['body'],
            payload: [
                'chat_id' => (int) $chat->getKey(),
                'message_id' => (int) $message->getKey(),
            ],
            dedupKey: NotificationDeduplicator::newMessage(
                (int) $message->getKey(),
                $partner->recipientType,
                $partner->recipientId(),
            ),
            actorType: $sender instanceof Officer ? ActorType::Officer : ActorType::User,
            actorId: (int) $sender->getKey(),
        );

        $filtered = $this->writer->excludeActor(collect([$insert]), $sender);

        $this->writer->writeMany($filtered);
    }

    private function actorDisplayName(Model $sender, Issue $canonical): string
    {
        if ($sender instanceof User) {
            $derived = IssueAnonymousDisplayName::derive($sender, $canonical);

            if ($derived['is_anonymous']) {
                return $derived['display_name'] ?? 'Anoniem';
            }

            return $sender->username;
        }

        if ($sender instanceof Officer) {
            return $sender->username;
        }

        throw new \InvalidArgumentException('New message notifications require a user or officer sender.');
    }
}
