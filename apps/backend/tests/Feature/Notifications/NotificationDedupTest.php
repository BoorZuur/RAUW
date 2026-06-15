<?php

namespace Tests\Feature\Notifications;

use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueOfficerAssignmentHistory;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Models\User;
use App\Support\Notifications\NotifyChatClosed;
use App\Support\Notifications\NotifyStatusChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationDedupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('notifications.enabled', true);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(object $actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    public function test_gesloten_creates_distinct_status_change_and_chat_closed_rows(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();

        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::Resolved)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/status", [
                'status' => IssueStatus::Closed->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::StatusChange->value,
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::ChatClosed->value,
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $this->assertSame(
            2,
            DomainNotification::query()->where('issue_id', $issue->id)->where('user_id', $owner->id)->count(),
        );
    }

    public function test_bulk_gesloten_close_reinvoking_notify_chat_closed_does_not_duplicate(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create();

        $issue = Issue::factory()->withStatus(IssueStatus::Closed)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $notify = new NotifyChatClosed;
        $notify->forChats($issue, collect([$chat]), $officer);
        $notify->forChats($issue, collect([$chat]), $officer);

        $this->assertSame(
            1,
            DomainNotification::query()
                ->where('type', NotificationType::ChatClosed)
                ->where('issue_id', $issue->id)
                ->count(),
        );
    }

    public function test_status_change_and_chat_closed_dedup_keys_are_independent(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create();

        $issue = Issue::factory()->withStatus(IssueStatus::Resolved)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        (new NotifyStatusChange)->notify($issue, $officer, IssueStatus::Resolved, IssueStatus::Closed);
        (new NotifyChatClosed)->notify($issue, $chat, $officer);

        $this->assertDatabaseCount('domain_notifications', 2);
    }

    public function test_feedback_submission_notifies_assigned_officer_with_unique_dedup_key(): void
    {
        $officer = Officer::factory()->create();
        $user = User::factory()->create();

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'status' => IssueStatus::Closed,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'is_anonymous' => false,
            'joined_via' => JoinedVia::Creator,
            'joined_at' => now(),
        ]);

        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDay(),
            'changed_by_officer_id' => $officer->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/feedback", [
                'is_satisfied' => true,
                'comment' => 'Great service',
            ])
            ->assertCreated();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::FeedbackReceived)
            ->where('issue_id', $issue->id)
            ->where('officer_id', $officer->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Feedback ontvangen', $notification->title);
        $this->assertStringContainsString($issue->title, $notification->body);
        $this->assertStringContainsString($user->username, $notification->body);
        $this->assertStringStartsWith("feedback_received:issue:{$issue->id}:feedback:", $notification->dedup_key);
    }

    public function test_feedback_notifies_resolution_officer_when_unassigned(): void
    {
        $resolutionOfficer = Officer::factory()->create();
        $user = User::factory()->create();

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'status' => IssueStatus::Closed,
            'assigned_officer_id' => null,
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'is_anonymous' => false,
            'joined_via' => JoinedVia::Creator,
            'joined_at' => now(),
        ]);

        OfficerIssueResolution::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $resolutionOfficer->id,
        ]);

        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDay(),
            'changed_by_officer_id' => $resolutionOfficer->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/feedback", [
                'is_satisfied' => false,
                'comment' => 'Could be better',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::FeedbackReceived->value,
            'issue_id' => $issue->id,
            'officer_id' => $resolutionOfficer->id,
        ]);
    }

    public function test_feedback_notifies_latest_assignment_history_officer_as_last_fallback(): void
    {
        $olderOfficer = Officer::factory()->create();
        $latestOfficer = Officer::factory()->create();
        $user = User::factory()->create();

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'status' => IssueStatus::Closed,
            'assigned_officer_id' => null,
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'is_anonymous' => false,
            'joined_via' => JoinedVia::Creator,
            'joined_at' => now(),
        ]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $olderOfficer->id,
            'assigned_at' => now()->subDays(2),
        ]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $latestOfficer->id,
            'assigned_at' => now()->subDay(),
        ]);

        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDay(),
            'changed_by_officer_id' => $latestOfficer->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/feedback", [
                'is_satisfied' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::FeedbackReceived->value,
            'issue_id' => $issue->id,
            'officer_id' => $latestOfficer->id,
        ]);
    }
}
