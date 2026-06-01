<?php

namespace Tests\Feature;

use App\Enums\ActorType;
use App\Enums\ChatStatus;
use App\Enums\Department;
use App\Enums\FlagAction;
use App\Enums\FlagReason;
use App\Enums\FlagSource;
use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Enums\Priority;
use App\Enums\ReportPeriod;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\ContentFlag;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueMessage;
use App\Models\IssueParticipant;
use App\Models\IssueStatusHistory;
use App\Models\Manager;
use App\Models\ReportSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnumCastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_enum_casts_round_trip(): void
    {
        $issue = $this->createIssue([
            'status' => IssueStatus::InProgress,
            'chat_status' => ChatStatus::Open,
            'priority' => Priority::High,
            'department' => Department::BoaYouth,
            'visibility' => Visibility::Hidden,
        ]);

        $issue->refresh();

        $this->assertSame(IssueStatus::InProgress, $issue->status);
        $this->assertSame(ChatStatus::Open, $issue->chat_status);
        $this->assertSame(Priority::High, $issue->priority);
        $this->assertSame(Department::BoaYouth, $issue->department);
        $this->assertSame(Visibility::Hidden, $issue->visibility);
    }

    public function test_related_enum_casts_round_trip(): void
    {
        $user = User::factory()->create();
        $issue = $this->createIssue(['user_id' => $user->id]);

        $participant = IssueParticipant::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'joined_via' => JoinedVia::Duplicate,
            'via_issue_id' => $issue->id,
        ]);

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'author_type' => ActorType::User,
            'user_id' => $user->id,
            'content' => 'Comment content',
            'visibility' => Visibility::Hidden,
        ]);

        $message = IssueMessage::create([
            'issue_id' => $issue->id,
            'sender_type' => ActorType::Officer,
            'content' => 'Message content',
        ]);

        $history = IssueStatusHistory::create([
            'issue_id' => $issue->id,
            'old_status' => IssueStatus::Open,
            'new_status' => IssueStatus::Resolved,
        ]);

        $notification = DomainNotification::create([
            'recipient_type' => ActorType::Manager,
            'issue_id' => $issue->id,
            'type' => 'issue_updated',
            'title' => 'Issue updated',
        ]);

        $flag = ContentFlag::create([
            'issue_id' => $issue->id,
            'comment_id' => $comment->id,
            'message_id' => $message->id,
            'flag_source' => FlagSource::Keyword,
            'flag_reason' => FlagReason::BlockedKeyword,
            'action_taken' => FlagAction::ContentHidden,
        ]);

        $manager = Manager::create([
            'username' => 'report-manager',
            'email' => 'report.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $snapshot = ReportSnapshot::create([
            'generated_by_manager_id' => $manager->id,
            'period' => ReportPeriod::Monthly,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ]);

        $this->assertSame(JoinedVia::Duplicate, $participant->refresh()->joined_via);
        $this->assertSame(ActorType::User, $comment->refresh()->author_type);
        $this->assertSame(Visibility::Hidden, $comment->visibility);
        $this->assertSame(ActorType::Officer, $message->refresh()->sender_type);
        $this->assertSame(IssueStatus::Open, $history->refresh()->old_status);
        $this->assertSame(IssueStatus::Resolved, $history->new_status);
        $this->assertSame(ActorType::Manager, $notification->refresh()->recipient_type);
        $this->assertSame(FlagSource::Keyword, $flag->refresh()->flag_source);
        $this->assertSame(FlagReason::BlockedKeyword, $flag->flag_reason);
        $this->assertSame(FlagAction::ContentHidden, $flag->action_taken);
        $this->assertSame(Department::Both, $manager->refresh()->department);
        $this->assertSame(ReportPeriod::Monthly, $snapshot->refresh()->period);
    }

    private function createIssue(array $attributes = []): Issue
    {
        $category = Category::create([
            'name' => 'Public space',
            'department' => Department::DistrictManagement,
        ]);

        return Issue::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Broken street light',
            'content' => 'The street light is broken.',
            'status' => IssueStatus::Open,
            'chat_status' => ChatStatus::Closed,
            'priority' => Priority::Low,
            'department' => Department::DistrictManagement,
            'visibility' => Visibility::Visible,
        ], $attributes));
    }
}
