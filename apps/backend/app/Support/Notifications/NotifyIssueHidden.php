<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotifyIssueHidden
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssueParticipants $participants = new ResolveCanonicalIssueParticipants,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, Model $actor): void
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        [$actorType, $actorDisplayName] = $this->resolveActor($actor);
        $copy = NotificationTemplates::issueHidden($canonical->title, $actorDisplayName);

        $inserts = $this->buildInserts($canonical, $actorType, $actor, $copy);
        $filtered = $this->writer->excludeActor($inserts, $actor);

        $this->writer->writeMany($filtered);
    }

    /**
     * @return array{0: ActorType, 1: string}
     */
    private function resolveActor(Model $actor): array
    {
        if ($actor instanceof Officer) {
            return [ActorType::Officer, $actor->username];
        }

        if ($actor instanceof Manager) {
            return [ActorType::Manager, $actor->username];
        }

        throw new \InvalidArgumentException('Issue hidden notifications require an officer or manager actor.');
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(
        Issue $canonical,
        ActorType $actorType,
        Model $actor,
        array $copy,
    ): Collection {
        return $this->participants
            ->userIds($canonical)
            ->map(static function (int $userId) use ($canonical, $actorType, $actor, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::User,
                    type: NotificationType::IssueHidden,
                    title: $copy['title'],
                    userId: $userId,
                    issueId: $canonical->getKey(),
                    body: $copy['body'],
                    dedupKey: NotificationDeduplicator::issueHidden($canonical->getKey(), $userId),
                    actorType: $actorType,
                    actorId: $actor->getKey(),
                );
            });
    }
}
