<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\IssueFeedback;
use App\Models\User;
use App\Support\Issues\IssueAnonymousDisplayName;

class NotifyFeedbackReceived
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveFeedbackRecipientOfficer $feedbackRecipient = new ResolveFeedbackRecipientOfficer,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, IssueFeedback $feedback, User $reviewer): void
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        $officerId = $this->feedbackRecipient->officerId($canonical);

        if ($officerId === null) {
            return;
        }

        $derived = IssueAnonymousDisplayName::derive($reviewer, $canonical);
        $actorDisplayName = $derived['is_anonymous']
            ? ($derived['display_name'] ?? 'Anoniem')
            : $reviewer->username;

        $copy = NotificationTemplates::feedbackReceived($canonical->title, $actorDisplayName);

        $insert = new NotificationInsert(
            recipientType: ActorType::Officer,
            type: NotificationType::FeedbackReceived,
            title: $copy['title'],
            officerId: $officerId,
            issueId: (int) $canonical->getKey(),
            body: $copy['body'],
            payload: ['feedback_id' => (int) $feedback->getKey()],
            dedupKey: NotificationDeduplicator::feedbackReceived(
                (int) $canonical->getKey(),
                (int) $feedback->getKey(),
            ),
            actorType: ActorType::User,
            actorId: (int) $reviewer->getKey(),
        );

        $filtered = $this->writer->excludeActor(collect([$insert]), $reviewer);

        $this->writer->writeMany($filtered);
    }
}
