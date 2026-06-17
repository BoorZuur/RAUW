const dateTimeFormatter = new Intl.DateTimeFormat('nl-NL', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

function getTypeSpecificLine(notification) {
    const payload = notification?.payload || {};

    switch (notification?.type) {
        case 'chat_opened':
        case 'chat_closed':
            return payload.chat_id ? `Chat #${payload.chat_id}` : null;
        case 'new_comment':
            return payload.comment_id ? `Reactie #${payload.comment_id}` : null;
        case 'feedback_received':
            return payload.feedback_id ? `Feedback #${payload.feedback_id}` : null;
        case 'new_community_post':
            return payload.community_post_id ? `Bericht #${payload.community_post_id}` : null;
        default:
            return null;
    }
}

export function getNotificationExpandedDetail(notification) {
    if (!notification) return [];

    const lines = [];

    if (notification.body) {
        lines.push(notification.body);
    }

    if (notification.created_at) {
        const createdAt = new Date(notification.created_at);
        if (!Number.isNaN(createdAt.getTime())) {
            lines.push(`Aangemaakt op ${dateTimeFormatter.format(createdAt)}`);
        }
    }

    const typeSpecificLine = getTypeSpecificLine(notification);
    if (typeSpecificLine) {
        lines.push(typeSpecificLine);
    }

    return lines;
}
