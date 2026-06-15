<?php

namespace Tests\Unit;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueFeedback;
use App\Support\Issues\IssueFeedbackWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueFeedbackWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_gesloten_at_uses_latest_history()
    {
        $issue = Issue::factory()->create(['status' => IssueStatus::Closed]);
        
        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(5),
            'changed_by_officer_id' => \App\Models\Officer::factory()->create()->id,
        ]);

        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Closed,
            'new_status' => IssueStatus::Open,
            'changed_at' => now()->subDays(4),
            'changed_by_officer_id' => \App\Models\Officer::factory()->create()->id,
        ]);

        $latestHistory = $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(2),
            'changed_by_officer_id' => \App\Models\Officer::factory()->create()->id,
        ]);

        $this->assertEquals($latestHistory->changed_at->toDateTimeString(), IssueFeedbackWindow::geslotenAt($issue)->toDateTimeString());
    }

    public function test_gesloten_at_fallback_to_updated_at()
    {
        $issue = Issue::factory()->create(['status' => IssueStatus::Closed, 'updated_at' => now()->subDays(3)]);
        
        $this->assertEquals($issue->updated_at->toDateTimeString(), IssueFeedbackWindow::geslotenAt($issue)->toDateTimeString());
    }

    public function test_is_open_within_7_days()
    {
        $issue = Issue::factory()->create(['status' => IssueStatus::Closed]);
        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(6),
            'changed_by_officer_id' => \App\Models\Officer::factory()->create()->id,
        ]);

        $this->assertTrue(IssueFeedbackWindow::isOpen($issue));
    }

    public function test_is_not_open_after_7_days()
    {
        $issue = Issue::factory()->create(['status' => IssueStatus::Closed]);
        $issue->statusHistory()->create([
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Closed,
            'changed_at' => now()->subDays(8),
            'changed_by_officer_id' => \App\Models\Officer::factory()->create()->id,
        ]);

        $this->assertFalse(IssueFeedbackWindow::isOpen($issue));
    }

    public function test_is_editable_within_1_day()
    {
        $feedback = IssueFeedback::factory()->create([
            'submitted_at' => now()->subHours(23),
        ]);

        $this->assertTrue(IssueFeedbackWindow::isEditable($feedback));
    }

    public function test_is_not_editable_after_1_day()
    {
        $feedback = IssueFeedback::factory()->create([
            'submitted_at' => now()->subHours(25),
        ]);

        $this->assertFalse(IssueFeedbackWindow::isEditable($feedback));
    }
}
