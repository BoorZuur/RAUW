<?php

namespace Tests\Feature;

use App\Enums\ActorType;
use App\Enums\ChatStatus;
use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Enums\Department;
use App\Enums\FlagAction;
use App\Enums\FlagReason;
use App\Enums\FlagSource;
use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Enums\NotificationType;
use App\Enums\Priority;
use App\Enums\ReportPeriod;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\ContentFlag;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueChat;
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
            'priority' => Priority::High,
            'department' => Department::BoaYouth,
            'visibility' => Visibility::Hidden,
        ]);

        $issue->refresh();

        $this->assertSame(IssueStatus::InProgress, $issue->status);
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

        $chat = IssueChat::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'status' => ChatStatus::Open,
        ]);

        $message = IssueMessage::create([
            'issue_chat_id' => $chat->id,
            'issue_id' => $issue->id,
            'message_type' => IssueMessageType::Message,
            'sender_type' => IssueMessageSenderType::Officer,
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
            'type' => NotificationType::StatusChange,
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
            'metrics' => ['priority_counts' => [Priority::Low->value => 1]],
        ]);

        $this->assertSame(JoinedVia::Duplicate, $participant->refresh()->joined_via);
        $this->assertSame(ActorType::User, $comment->refresh()->author_type);
        $this->assertSame(Visibility::Hidden, $comment->visibility);
        $this->assertSame(ChatStatus::Open, $chat->refresh()->status);
        $this->assertSame(IssueMessageSenderType::Officer, $message->refresh()->sender_type);
        $this->assertSame(IssueMessageType::Message, $message->message_type);
        $this->assertSame(IssueStatus::Open, $history->refresh()->old_status);
        $this->assertSame(IssueStatus::Resolved, $history->new_status);
        $this->assertSame(ActorType::Manager, $notification->refresh()->recipient_type);
        $this->assertSame(NotificationType::StatusChange, $notification->type);
        $this->assertSame(FlagSource::Keyword, $flag->refresh()->flag_source);
        $this->assertSame(FlagReason::BlockedKeyword, $flag->flag_reason);
        $this->assertSame(FlagAction::ContentHidden, $flag->action_taken);
        $this->assertSame(Department::Both, $manager->refresh()->department);
        $this->assertSame(ReportPeriod::Monthly, $snapshot->refresh()->period);
        $this->assertSame(1, $snapshot->metrics['priority_counts'][Priority::Low->value]);
    }

    private function createIssue(array $attributes = []): Issue
    {
        // Categories no longer carry a `department` enum column; departments are
        // a first-class table assigned through the `category_department` pivot.
        $category = Category::create([
            'name' => 'Public space',
        ]);

        return Issue::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Broken street light',
            'content' => 'The street light is broken.',
            'status' => IssueStatus::Open,
            'priority' => Priority::Low,
            'department' => Department::DistrictManagement,
            'visibility' => Visibility::Visible,
        ], $attributes));
    }
}
