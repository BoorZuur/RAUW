<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\CommunityPost;
use App\Models\Officer;
use Illuminate\Support\Collection;

class NotifyNewCommunityPost
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveDistrictFeedFollowers $feedFollowers = new ResolveDistrictFeedFollowers,
    ) {}

    public function notify(CommunityPost $post, Officer $publisher): void
    {
        $copy = NotificationTemplates::newCommunityPost($publisher->username);

        $inserts = $this->buildInserts($post, $publisher, $copy);
        $filtered = $this->writer->excludeActor($inserts, $publisher);

        $this->writer->writeMany($filtered);
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(CommunityPost $post, Officer $publisher, array $copy): Collection
    {
        return $this->feedFollowers
            ->userIds($post)
            ->map(static function (int $userId) use ($post, $publisher, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::User,
                    type: NotificationType::NewCommunityPost,
                    title: $copy['title'],
                    userId: $userId,
                    communityPostId: (int) $post->getKey(),
                    body: $copy['body'],
                    dedupKey: NotificationDeduplicator::newCommunityPost(),
                    actorType: ActorType::Officer,
                    actorId: (int) $publisher->getKey(),
                );
            });
    }
}
