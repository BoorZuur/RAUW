<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\Issue;
use App\Models\User;
use App\Support\Issues\IssueAnonymousDisplayName;
use Illuminate\Support\Collection;

class NotifyNewIssue
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveDistrictOfficersForIssue $districtOfficers = new ResolveDistrictOfficersForIssue,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
    ) {}

    public function notify(Issue $issue, User $creator): void
    {
        $canonical = ($this->resolveCanonicalIssue)($issue);
        $actorDisplayName = $this->userDisplayName($creator, $canonical);
        $copy = NotificationTemplates::newIssue($canonical->title, $actorDisplayName);

        $inserts = $this->buildInserts($canonical, $creator, $copy);
        $filtered = $this->writer->excludeActor($inserts, $creator);

        $this->writer->writeMany($filtered);
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(Issue $canonical, User $creator, array $copy): Collection
    {
        return $this->districtOfficers
            ->officerIds($canonical)
            ->map(static function (int $officerId) use ($canonical, $creator, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::Officer,
                    type: NotificationType::NewIssue,
                    title: $copy['title'],
                    officerId: $officerId,
                    issueId: $canonical->getKey(),
                    body: $copy['body'],
                    dedupKey: NotificationDeduplicator::newIssue(),
                    actorType: ActorType::User,
                    actorId: $creator->getKey(),
                );
            });
    }

    private function userDisplayName(User $user, Issue $issue): string
    {
        $derived = IssueAnonymousDisplayName::derive($user, $issue);

        if ($derived['is_anonymous']) {
            return $derived['display_name'] ?? 'Anoniem';
        }

        return $user->username;
    }
}
