<?php

namespace App\Support\Notifications;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Enums\Visibility;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueCommentAnonymity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotifyNewComment
{
    public function __construct(
        private readonly NotificationWriter $writer = new NotificationWriter,
        private readonly ResolveCanonicalIssueParticipants $participants = new ResolveCanonicalIssueParticipants,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue = new ResolveCanonicalIssue,
        private readonly IssueCommentAnonymity $commentAnonymity = new IssueCommentAnonymity,
    ) {}

    public function notify(Issue $issue, IssueComment $comment, Model $author): void
    {
        if ($comment->visibility !== Visibility::Visible) {
            return;
        }

        $canonical = ($this->resolveCanonicalIssue)($issue);
        [$actorType, $actorDisplayName] = $this->resolveActor($author, $canonical, $comment);
        $copy = NotificationTemplates::newComment($canonical->title, $actorDisplayName);

        $inserts = $this->buildInserts($canonical, $comment, $actorType, $author, $copy);
        $filtered = $this->writer->excludeActor($inserts, $author);

        $this->writer->writeMany($filtered);
    }

    /**
     * @return array{0: ActorType, 1: string}
     */
    private function resolveActor(Model $author, Issue $canonical, IssueComment $comment): array
    {
        if ($author instanceof User) {
            if ((bool) $comment->is_anonymous === true) {
                $comment->loadMissing('user');
                $comment->setRelation('issue', $canonical);
                $resolved = $this->commentAnonymity->resolveDisplayName($comment);

                return [ActorType::User, $resolved['display_name'] ?? 'Anoniem'];
            }

            return [ActorType::User, $author->username];
        }

        if ($author instanceof Officer) {
            return [ActorType::Officer, $author->username];
        }

        if ($author instanceof Manager) {
            return [ActorType::Manager, $author->username];
        }

        throw new \InvalidArgumentException('New comment notifications require a user, officer, or manager author.');
    }

    /**
     * @param  array{title: string, body: string}  $copy
     * @return Collection<int, NotificationInsert>
     */
    private function buildInserts(
        Issue $canonical,
        IssueComment $comment,
        ActorType $actorType,
        Model $author,
        array $copy,
    ): Collection {
        $inserts = $this->participants
            ->userIds($canonical)
            ->map(static function (int $userId) use ($canonical, $comment, $actorType, $author, $copy): NotificationInsert {
                return new NotificationInsert(
                    recipientType: ActorType::User,
                    type: NotificationType::NewComment,
                    title: $copy['title'],
                    userId: $userId,
                    issueId: (int) $canonical->getKey(),
                    body: $copy['body'],
                    payload: ['comment_id' => (int) $comment->getKey()],
                    dedupKey: NotificationDeduplicator::newComment(
                        (int) $comment->getKey(),
                        ActorType::User,
                        $userId,
                    ),
                    actorType: $actorType,
                    actorId: (int) $author->getKey(),
                );
            });

        $assignedOfficerId = $this->participants->assignedOfficerId($canonical);

        if ($assignedOfficerId !== null) {
            $inserts->push(new NotificationInsert(
                recipientType: ActorType::Officer,
                type: NotificationType::NewComment,
                title: $copy['title'],
                officerId: $assignedOfficerId,
                issueId: (int) $canonical->getKey(),
                body: $copy['body'],
                payload: ['comment_id' => (int) $comment->getKey()],
                dedupKey: NotificationDeduplicator::newComment(
                    (int) $comment->getKey(),
                    ActorType::Officer,
                    $assignedOfficerId,
                ),
                actorType: $actorType,
                actorId: (int) $author->getKey(),
            ));
        }

        return $inserts->values();
    }
}
