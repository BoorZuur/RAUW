<?php

namespace App\Enums;

enum NotificationType: string
{
    case StatusChange = 'status_change';
    case NewMessage = 'new_message';
    case NewIssue = 'new_issue';
    case ChatOpened = 'chat_opened';
    case ChatClosed = 'chat_closed';
    case NewComment = 'new_comment';
    case ResolutionPosted = 'resolution_posted';
    case FeedbackReceived = 'feedback_received';
    case NewCommunityPost = 'new_community_post';
    case IssueHidden = 'issue_hidden';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
