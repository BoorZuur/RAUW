<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\IssueStatus;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\Officer;
use App\Support\IssueStatusTransition;
use Illuminate\Support\Collection;

class NotifyStatusChange
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssueParticipants $participants = new ResolveCanonicalIssueParticipants,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(
        Issue $issue,
        Officer $officer,
        IssueStatus $oldStatus,
        IssueStatus $newStatus,
    ): void {
        if (! IssueStatusTransition::canTransition($oldStatus, $newStatus)) {
            return;
        }

        $canonical = ($this->resolveCanonicalIssue)($issue);
        $copy = NotificationTemplates::statusChange(
            $canonical->title,
            $officer->username,
            $newStatus,
        );

        $inserts = $this->buildInserts($canonical, $officer, $newStatus, $copy);
        $filtered = $this->writer->excludeActor($inserts, $officer);

        $this->writer->writeMany($filtered);
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(
        Issue $canonical,
        Officer $officer,
        IssueStatus $newStatus,
        array $copy,
    ): Collection {
        return $this->participants
            ->userIds($canonical)
            ->map(static function (int $userId) use ($canonical, $officer, $newStatus, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::User,
                    type: NotificationType::StatusChange,
                    title: $copy['title'],
                    userId: $userId,
                    issueId: $canonical->getKey(),
                    body: $copy['body'],
                    dedupKey: NotificationDeduplicator::statusChange(
                        $canonical->getKey(),
                        $userId,
                        $newStatus->value,
                    ),
                    actorType: ActorType::Officer,
                    actorId: $officer->getKey(),
                );
            });
    }
}
