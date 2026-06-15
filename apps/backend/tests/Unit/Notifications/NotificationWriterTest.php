<?php

namespace Tests\Unit\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\User;
use App\Support\Notifications\NotificationDeduplicator;
use App\Support\Notifications\NotificationInsert;
use App\Support\Notifications\NotificationWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationWriterTest extends TestCase
{
    use RefreshDatabase;

    private NotificationWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new NotificationWriter;
        Config::set('notifications.enabled', true);
    }

    public function test_exclude_actor_removes_matching_user_recipient(): void
    {
        $actor = User::factory()->create();
        $otherUser = User::factory()->create();
        $issue = Issue::factory()->create();

        $inserts = collect([
            $this->userInsert($actor->id, $issue->id),
            $this->userInsert($otherUser->id, $issue->id),
        ]);

        $filtered = $this->writer->excludeActor($inserts, $actor);

        $this->assertCount(1, $filtered);
        $this->assertSame($otherUser->id, $filtered->first()->userId);
    }

    public function test_exclude_actor_removes_matching_officer_recipient(): void
    {
        $actor = Officer::factory()->create();
        $otherOfficer = Officer::factory()->create();
        $issue = Issue::factory()->create();

        $inserts = collect([
            $this->officerInsert($actor->id, $issue->id),
            $this->officerInsert($otherOfficer->id, $issue->id),
        ]);

        $filtered = $this->writer->excludeActor($inserts, $actor);

        $this->assertCount(1, $filtered);
        $this->assertSame($otherOfficer->id, $filtered->first()->officerId);
    }

    public function test_duplicate_dedup_key_does_not_create_second_row(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();
        $dedupKey = NotificationDeduplicator::statusChange($issue->id, $user->id, 'gesloten');

        $insert = new NotificationInsert(
            recipientType: ActorType::User,
            type: NotificationType::StatusChange,
            title: 'Status gewijzigd',
            userId: $user->id,
            issueId: $issue->id,
            dedupKey: $dedupKey,
        );

        $this->assertNotNull($this->writer->write($insert));
        $this->assertNull($this->writer->write($insert));
        $this->assertDatabaseCount('domain_notifications', 1);
    }

    public function test_in_memory_dedup_key_deduplicates_within_single_batch(): void
    {
        $users = User::factory()->count(2)->create();
        $issue = Issue::factory()->create();
        $dedupKey = NotificationDeduplicator::issueHidden($issue->id, $users[0]->id);

        $inserts = collect([
            new NotificationInsert(
                recipientType: ActorType::User,
                type: NotificationType::IssueHidden,
                title: 'Melding verborgen',
                userId: $users[0]->id,
                issueId: $issue->id,
                dedupKey: $dedupKey,
            ),
            new NotificationInsert(
                recipientType: ActorType::User,
                type: NotificationType::IssueHidden,
                title: 'Melding verborgen',
                userId: $users[1]->id,
                issueId: $issue->id,
                dedupKey: $dedupKey,
            ),
        ]);

        $this->assertSame(1, $this->writer->writeMany($inserts));
        $this->assertDatabaseCount('domain_notifications', 1);
    }

    public function test_disabled_config_prevents_inserts(): void
    {
        Config::set('notifications.enabled', false);

        $user = User::factory()->create();
        $issue = Issue::factory()->create();

        $insert = $this->userInsert($user->id, $issue->id);

        $this->assertNull($this->writer->write($insert));
        $this->assertSame(0, $this->writer->writeMany(collect([$insert])));
        $this->assertDatabaseCount('domain_notifications', 0);
    }

    public function test_write_many_with_one_hundred_recipients_uses_single_bulk_insert(): void
    {
        $users = User::factory()->count(100)->create();
        $issue = Issue::factory()->create();

        $inserts = $users->map(fn (User $user): NotificationInsert => $this->userInsert($user->id, $issue->id));

        $insertQueryCount = 0;

        DB::listen(function ($query) use (&$insertQueryCount): void {
            if (
                str_contains(strtolower($query->sql), 'insert')
                && str_contains(strtolower($query->sql), 'domain_notifications')
            ) {
                $insertQueryCount++;
            }
        });

        $inserted = $this->writer->writeMany($inserts);

        $this->assertSame(100, $inserted);
        $this->assertDatabaseCount('domain_notifications', 100);
        $this->assertSame(1, $insertQueryCount);
    }

    public function test_notification_insert_rejects_mismatched_recipient_foreign_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient foreign key does not match recipient type.');

        new NotificationInsert(
            recipientType: ActorType::User,
            type: NotificationType::NewIssue,
            title: 'Nieuwe melding',
            officerId: Officer::factory()->create()->id,
        );
    }

    public function test_notification_insert_rejects_multiple_recipient_foreign_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one recipient foreign key must be set.');

        new NotificationInsert(
            recipientType: ActorType::User,
            type: NotificationType::NewIssue,
            title: 'Nieuwe melding',
            userId: User::factory()->create()->id,
            officerId: Officer::factory()->create()->id,
        );
    }

    private function userInsert(int $userId, int $issueId): NotificationInsert
    {
        return new NotificationInsert(
            recipientType: ActorType::User,
            type: NotificationType::NewIssue,
            title: 'Nieuwe melding',
            userId: $userId,
            issueId: $issueId,
        );
    }

    private function officerInsert(int $officerId, int $issueId): NotificationInsert
    {
        return new NotificationInsert(
            recipientType: ActorType::Officer,
            type: NotificationType::NewIssue,
            title: 'Nieuwe melding',
            officerId: $officerId,
            issueId: $issueId,
        );
    }
}
