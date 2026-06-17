<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\IssueFeedback;
use App\Models\IssueOfficerAssignmentHistory;
use App\Models\Officer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerMeFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_view_feedback_for_involved_issues()
    {
        $officer = Officer::factory()->create();
        $issue = Issue::factory()->create();
        
        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
            'assigned_at' => now(),
        ]);

        $feedback = IssueFeedback::factory()->create([
            'issue_id' => $issue->id,
        ]);

        $response = $this->actingAs($officer)->getJson("/api/officers/me/feedback");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $feedback->id);
    }

    public function test_officer_cannot_view_feedback_for_uninvolved_issues()
    {
        $officer = Officer::factory()->create();
        $otherOfficer = Officer::factory()->create();
        $issue = Issue::factory()->create();
        
        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $otherOfficer->id,
            'assigned_at' => now(),
        ]);

        IssueFeedback::factory()->create([
            'issue_id' => $issue->id,
        ]);

        $response = $this->actingAs($officer)->getJson("/api/officers/me/feedback");

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
