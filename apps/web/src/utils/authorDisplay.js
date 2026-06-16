export function getIssueAuthorDisplayName(issue) {
    return issue?.author?.display_name || issue?.author?.username || 'Buurtbewoner';
}

export function getCommentAuthorDisplayName(comment) {
    if (comment?.author?.display_name) {
        return comment.author.display_name;
    }

    if (comment?.author?.username) {
        return comment.author.username;
    }

    return 'Buurtbewoner';
}
