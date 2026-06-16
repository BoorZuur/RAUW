import {
    Bell,
    CheckCircle,
    Megaphone,
    MessageCircle,
    MessageSquare,
    Newspaper,
    RefreshCw,
    Star,
} from 'lucide-react';

const typeIconMap = {
    status_change: RefreshCw,
    new_message: MessageCircle,
    chat_opened: MessageCircle,
    chat_closed: MessageCircle,
    new_issue: Megaphone,
    issue_hidden: Megaphone,
    new_comment: MessageSquare,
    resolution_posted: CheckCircle,
    feedback_received: Star,
    new_community_post: Newspaper,
};

export function getNotificationIcon(type) {
    return typeIconMap[type] || Bell;
}
