<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResolveIssueChatPartner
{
    /**
     * Recipient target for the other party in a 1:1 issue chat.
     *
     * Returns null when a user sends but the canonical issue has no assigned officer.
     */
    public function resolve(IssueChat $chat, Issue $canonical, Model $sender): ?NotificationInsert
    {
        if ($sender instanceof Officer) {
            return new NotificationInsert(
                recipientType: ActorType::User,
                type: NotificationType::NewMessage,
                title: '',
                userId: (int) $chat->user_id,
                issueId: (int) $canonical->getKey(),
            );
        }

        if ($sender instanceof User) {
            if ($canonical->assigned_officer_id === null) {
                return null;
            }

            return new NotificationInsert(
                recipientType: ActorType::Officer,
                type: NotificationType::NewMessage,
                title: '',
                officerId: (int) $canonical->assigned_officer_id,
                issueId: (int) $canonical->getKey(),
            );
        }

        return null;
    }
}
