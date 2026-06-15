<?php

namespace Tests\Feature\Notifications;

use App\Enums\IssueStatus;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\Department;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use App\Support\Notifications\NotifyStatusChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationStatusChangeTest extends TestCase
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

    public function test_status_transition_notifies_canonical_participants(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $participant = User::factory()->create();

        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/status", [
                'status' => IssueStatus::InProgress->value,
            ])
            ->assertOk()
            ->assertJsonPath('status', IssueStatus::InProgress->value);

        $notifications = DomainNotification::query()
            ->where('type', NotificationType::StatusChange)
            ->where('issue_id', $issue->id)
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$owner->id, $participant->id],
            $notifications->pluck('user_id')->all(),
        );

        $notification = $notifications->first();
        $this->assertSame('Status gewijzigd', $notification->title);
        $this->assertStringContainsString($issue->title, $notification->body);
        $this->assertStringContainsString($officer->username, $notification->body);
        $this->assertSame('officer', $notification->actor_type);
        $this->assertSame($officer->id, $notification->actor_id);
    }

    public function test_self_assign_open_to_in_behandeling_notifies_participants(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();

        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => null,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/assign-self")
            ->assertOk()
            ->assertJsonPath('status', IssueStatus::InProgress->value);

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::StatusChange->value,
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'dedup_key' => "status_change:issue:{$issue->id}:user:{$owner->id}:to:in_behandeling",
        ]);
    }

    public function test_status_change_dedup_key_prevents_duplicate_rows(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();

        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $notify = new NotifyStatusChange;

        $notify->notify($issue, $officer, IssueStatus::Open, IssueStatus::InProgress);
        $notify->notify($issue, $officer, IssueStatus::Open, IssueStatus::InProgress);

        $this->assertDatabaseCount('domain_notifications', 1);
    }
}
