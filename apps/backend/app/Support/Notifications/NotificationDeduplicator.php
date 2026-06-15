<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;

class NotificationDeduplicator
{
    public static function statusChange(int $issueId, int $userId, string $status): string
    {
        return "status_change:issue:{$issueId}:user:{$userId}:to:{$status}";
    }

    public static function chatClosed(int $issueId, int $chatId, int $userId): string
    {
        return "chat_closed:issue:{$issueId}:chat:{$chatId}:user:{$userId}";
    }

    public static function chatOpened(int $issueId, int $chatId, int $userId): string
    {
        return "chat_opened:issue:{$issueId}:chat:{$chatId}:user:{$userId}";
    }

    public static function newMessage(int $messageId, ActorType $recipientType, int $recipientId): string
    {
        return "new_message:message:{$messageId}:recipient:{$recipientType->value}:{$recipientId}";
    }

    public static function newComment(int $commentId, ActorType $recipientType, int $recipientId): string
    {
        return "new_comment:comment:{$commentId}:recipient:{$recipientType->value}:{$recipientId}";
    }

    public static function issueHidden(int $issueId, int $userId): string
    {
        return "issue_hidden:issue:{$issueId}:user:{$userId}";
    }

    public static function feedbackReceived(int $issueId, int $feedbackId): string
    {
        return "feedback_received:issue:{$issueId}:feedback:{$feedbackId}";
    }

    public static function resolutionPosted(): ?string
    {
        return null;
    }

    public static function newIssue(): ?string
    {
        return null;
    }

    public static function newCommunityPost(): ?string
    {
        return null;
    }
}
