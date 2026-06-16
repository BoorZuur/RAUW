/**
 * Resolve the issue id for join, leave, and participation refetch.
 *
 * When the opened row is a duplicate child (`is_duplicate_child`), the API expects
 * the canonical parent id — posting to the child id returns 422
 * `cannot_join_duplicate_child`. Leave and GET refetch use the same target so
 * counts and `is_participant` stay in sync with the canonical row.
 *
 * For canonical issues and public feed entries, `canonical_issue_id` is absent
 * and the issue's own `id` is used.
 */
export function resolveJoinTargetId(issue) {
    return issue?.canonical_issue_id ?? issue?.id;
}

export function isIssueContentRedacted() {
    return false;
}

export function getIssueDisplayTitle(issue) {
    if (!issue) {
        return '';
    }

    return issue.title ?? '';
}

export function getIssueDisplayContent(issue) {
    if (!issue) {
        return '';
    }

    return issue.content ?? '';
}
