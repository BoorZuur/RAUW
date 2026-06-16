const REDACTED_TITLE = 'Melding die je volgt';
const REDACTED_CONTENT =
    'Om de privacy van de melder te beschermen is de inhoud van deze melding niet zichtbaar voor volgers.';

/** Join/leave/refetch must target the canonical issue when viewing a duplicate child. */
export function resolveJoinTargetId(issue) {
    return issue?.canonical_issue_id ?? issue?.id;
}

export function isIssueContentRedacted(issue) {
    return Boolean(
        issue?.is_participant
        && !issue?.is_duplicate_child
        && (issue?.title == null || issue?.content == null),
    );
}

export function getIssueDisplayTitle(issue) {
    if (!issue) {
        return '';
    }

    if (isIssueContentRedacted(issue)) {
        return REDACTED_TITLE;
    }

    return issue.title ?? '';
}

export function getIssueDisplayContent(issue) {
    if (!issue) {
        return '';
    }

    if (isIssueContentRedacted(issue)) {
        return REDACTED_CONTENT;
    }

    return issue.content ?? '';
}
