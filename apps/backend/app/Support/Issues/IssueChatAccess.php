<?php

namespace App\Support\Issues;

use App\Enums\ChatStatus;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssueChatConflict;
use Illuminate\Database\Eloquent\Model;

class IssueChatAccess
{
    /**
     * Whether the actor may list chats on the canonical issue.
     *
     * Assignee officers see all chats. Eligible users (owner or participant)
     * see only their own chats in the controller layer but always receive 200.
     */
    public static function canListChatsOnIssue(Model $actor, Issue $canonical): bool
    {
        if ($actor instanceof Manager) {
            return false;
        }

        if ($actor instanceof Officer) {
            return $canonical->assigned_officer_id === $actor->getKey();
        }

        if ($actor instanceof User) {
            return self::isEligibleUser($actor, $canonical);
        }

        return false;
    }

    /**
     * Whether the actor may read messages in a specific chat (open or closed).
     */
    public static function canViewChat(Model $actor, IssueChat $chat, Issue $canonical): bool
    {
        if ($chat->issue_id !== $canonical->getKey()) {
            return false;
        }

        if ($actor instanceof Manager) {
            return false;
        }

        if ($actor instanceof Officer) {
            return $canonical->assigned_officer_id === $actor->getKey();
        }

        if ($actor instanceof User) {
            return $chat->user_id === $actor->getKey();
        }

        return false;
    }

    /**
     * Whether the actor may send messages in an open chat.
     */
    public static function canSendInChat(Model $actor, IssueChat $chat, Issue $canonical): bool
    {
        if ($chat->status !== ChatStatus::Open) {
            return false;
        }

        return self::canViewChat($actor, $chat, $canonical);
    }

    /**
     * Assert the actor may access the chat or throw 403.
     */
    public static function assertCanViewChat(Model $actor, IssueChat $chat, Issue $canonical): void
    {
        if ($chat->issue_id !== $canonical->getKey()) {
            throw IssueChatConflict::chatNotFound();
        }

        if (! self::canViewChat($actor, $chat, $canonical)) {
            throw IssueChatConflict::notChatParticipant();
        }
    }

    /**
     * Assert the actor may send in the chat or throw the appropriate conflict.
     */
    public static function assertCanSendInChat(Model $actor, IssueChat $chat, Issue $canonical): void
    {
        self::assertCanViewChat($actor, $chat, $canonical);

        if ($chat->status !== ChatStatus::Open) {
            throw IssueChatConflict::chatClosed();
        }
    }

    /**
     * Whether the user is the canonical owner or a participant row exists.
     */
    public static function isEligibleUser(User $user, Issue $canonical): bool
    {
        return IssueChatParticipantEligibility::isEligible($user, $canonical);
    }
}
