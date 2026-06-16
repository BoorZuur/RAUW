<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\IssueStatusHistory;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OfficerIssueResolutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(object $actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    /**
     * @return array{issue: Issue, officer: Officer, owner: User}
     */
    private function assignedInProgressIssueContext(?IssueStatus $status = null): array
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus($status ?? IssueStatus::InProgress)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        return compact('issue', 'officer', 'owner');
    }

    public function test_store_transitions_in_behandeling_to_opgelost(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedInProgressIssueContext();

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", [
                'title' => 'Resolved on site',
                'content' => 'Replaced the lamp.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'status' => IssueStatus::Resolved->value,
        ]);

        $issue->refresh();
        $this->assertNotNull($issue->resolved_at);

        $this->assertDatabaseHas('issue_status_histories', [
            'issue_id' => $issue->id,
            'changed_by_officer_id' => $officer->id,
            'old_status' => IssueStatus::InProgress->value,
            'new_status' => IssueStatus::Resolved->value,
            'note' => 'Oplossing geplaatst',
        ]);

        $this->assertSame(
            1,
            IssueStatusHistory::query()->where('issue_id', $issue->id)->count(),
        );
    }

    public function test_store_does_not_transition_open_issue(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedInProgressIssueContext(IssueStatus::Open);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", [
                'title' => 'Resolution while open',
                'content' => 'Work in progress.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'status' => IssueStatus::Open->value,
            'resolved_at' => null,
        ]);

        $this->assertDatabaseCount('issue_status_histories', 0);
    }

    public function test_patch_does_not_change_status(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedInProgressIssueContext();

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", [
                'title' => 'Initial resolution',
                'content' => 'First pass.',
            ])
            ->assertCreated();

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/officer-resolution", [
                'title' => 'Updated resolution',
                'content' => 'Added details.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'status' => IssueStatus::Resolved->value,
        ]);

        $this->assertSame(
            1,
            IssueStatusHistory::query()->where('issue_id', $issue->id)->count(),
        );
    }

    public function test_store_duplicate_resolution_returns_409_without_second_history(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedInProgressIssueContext();

        $payload = [
            'title' => 'Resolved on site',
            'content' => 'Replaced the lamp.',
        ];

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", $payload)
            ->assertCreated();

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", $payload)
            ->assertConflict()
            ->assertJsonPath('code', 'officer_resolution_exists');

        $this->assertSame(
            1,
            IssueStatusHistory::query()->where('issue_id', $issue->id)->count(),
        );
    }

    public function test_store_does_not_emit_status_change_notification(): void
    {
        Config::set('notifications.enabled', true);

        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedInProgressIssueContext();

        $participant = User::factory()->create();
        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-resolution", [
                'title' => 'Resolved on site',
                'content' => 'Replaced the lamp.',
            ])
            ->assertCreated();

        $this->assertGreaterThan(
            0,
            DomainNotification::query()
                ->where('type', NotificationType::ResolutionPosted)
                ->where('issue_id', $issue->id)
                ->count(),
        );

        $this->assertSame(
            0,
            DomainNotification::query()
                ->where('type', NotificationType::StatusChange)
                ->where('issue_id', $issue->id)
                ->count(),
        );
    }
}
