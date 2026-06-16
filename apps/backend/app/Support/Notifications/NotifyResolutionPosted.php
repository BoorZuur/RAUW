<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\Officer;
use Illuminate\Support\Collection;

class NotifyResolutionPosted
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssueParticipants $participants = new ResolveCanonicalIssueParticipants,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, Officer $officer): void
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        $copy = NotificationTemplates::resolutionPosted($canonical->title, $officer->username);

        $inserts = $this->buildInserts($canonical, $officer, $copy);
        $filtered = $this->writer->excludeActor($inserts, $officer);

        $this->writer->writeMany($filtered);
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(Issue $canonical, Officer $officer, array $copy): Collection
    {
        return $this->participants
            ->userIds($canonical)
            ->map(static function (int $userId) use ($canonical, $officer, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::User,
                    type: NotificationType::ResolutionPosted,
                    title: $copy['title'],
                    userId: $userId,
                    issueId: (int) $canonical->getKey(),
                    body: $copy['body'],
                    dedupKey: NotificationDeduplicator::resolutionPosted(),
                    actorType: ActorType::Officer,
                    actorId: (int) $officer->getKey(),
                );
            });
    }
}
