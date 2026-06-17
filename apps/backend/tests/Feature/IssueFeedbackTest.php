<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueFeedback;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Issue $issue;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->issue = Issue::factory()->create(['user_id' => $this->user->id, 'status' => IssueStatus::Closed]);
        
        IssueParticipant::query()->create([
            'issue_id' => $this->issue->id,
            'user_id' => $this->user->id,
            'is_anonymous' => false,
            'joined_via' => JoinedVia::Creator,
            'joined_at' => now(),
        ]);

        $this->issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(1),
            'changed_by_officer_id' => Officer::factory()->create()->id,
        ]);
    }

    public function test_user_can_submit_feedback()
    {
        $response = $this->actingAs($this->user)->postJson("/api/issues/{$this->issue->id}/feedback", [
            'is_satisfied' => true,
            'comment' => 'Great job!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('issue_feedback', [
            'issue_id' => $this->issue->id,
            'reviewer_user_id' => $this->user->id,
            'is_satisfied' => 1,
            'comment' => 'Great job!',
        ]);
    }

    public function test_cannot_submit_feedback_outside_window()
    {
        $this->issue->statusHistory()->delete();
        $this->issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(8),
            'changed_by_officer_id' => Officer::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/issues/{$this->issue->id}/feedback", [
            'is_satisfied' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_submit_duplicate_feedback()
    {
        IssueFeedback::factory()->create([
            'issue_id' => $this->issue->id,
            'reviewer_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/issues/{$this->issue->id}/feedback", [
            'is_satisfied' => true,
        ]);

        $response->assertStatus(409);
    }

    public function test_user_can_update_feedback_within_edit_window()
    {
        $feedback = IssueFeedback::factory()->create([
            'issue_id' => $this->issue->id,
            'reviewer_user_id' => $this->user->id,
            'is_satisfied' => true,
            'submitted_at' => now()->subHours(10),
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/issues/{$this->issue->id}/feedback/{$feedback->id}", [
            'is_satisfied' => false,
            'comment' => 'Changed my mind',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('issue_feedback', [
            'id' => $feedback->id,
            'is_satisfied' => 0,
            'comment' => 'Changed my mind',
        ]);
    }

    public function test_cannot_update_feedback_after_edit_window()
    {
        $feedback = IssueFeedback::factory()->create([
            'issue_id' => $this->issue->id,
            'reviewer_user_id' => $this->user->id,
            'submitted_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/issues/{$this->issue->id}/feedback/{$feedback->id}", [
            'is_satisfied' => false,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_feedback_within_edit_window()
    {
        $feedback = IssueFeedback::factory()->create([
            'issue_id' => $this->issue->id,
            'reviewer_user_id' => $this->user->id,
            'submitted_at' => now()->subHours(10),
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/issues/{$this->issue->id}/feedback/{$feedback->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('issue_feedback', ['id' => $feedback->id]);
    }
}
