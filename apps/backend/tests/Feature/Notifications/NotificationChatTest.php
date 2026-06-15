<?php

namespace Tests\Feature\Notifications;

use App\Enums\ChatStatus;
use App\Enums\IssueMessageType;
use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use App\Support\Notifications\NotifyChatClosed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationChatTest extends TestCase
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

    /**
     * @return array{issue: Issue, officer: Officer, owner: User, chat: IssueChat}
     */
    private function openChatContext(): array
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::InProgress)->create([
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

        return compact('issue', 'officer', 'owner', 'chat');
    }

    public function test_officer_message_notifies_chat_user(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chat->id}/messages",
            ['content' => 'Hello from officer'],
            $this->authHeaders($officer),
        )->assertCreated();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::NewMessage)
            ->where('issue_id', $issue->id)
            ->where('user_id', $owner->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Nieuw bericht', $notification->title);
        $this->assertStringContainsString($issue->title, $notification->body);
        $this->assertStringContainsString($officer->username, $notification->body);
        $this->assertSame('officer', $notification->actor_type);
        $this->assertSame($officer->id, $notification->actor_id);

        $messageId = IssueMessage::query()
            ->where('issue_chat_id', $chat->id)
            ->where('message_type', IssueMessageType::Message)
            ->value('id');

        $this->assertSame(
            "new_message:message:{$messageId}:recipient:user:{$owner->id}",
            $notification->dedup_key,
        );
    }

    public function test_user_message_notifies_assigned_officer(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chat->id}/messages",
            ['content' => 'Hello from citizen'],
            $this->authHeaders($owner),
        )->assertCreated();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::NewMessage)
            ->where('issue_id', $issue->id)
            ->where('officer_id', $officer->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Nieuw bericht', $notification->title);
        $this->assertSame('user', $notification->actor_type);
        $this->assertSame($owner->id, $notification->actor_id);
    }

    public function test_chat_opened_notifies_chat_user(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->openChatContext();

        IssueChat::query()
            ->where('issue_id', $issue->id)
            ->where('user_id', $owner->id)
            ->update(['status' => ChatStatus::Closed]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ])
            ->assertOk();

        $chat = IssueChat::query()
            ->where('issue_id', $issue->id)
            ->where('user_id', $owner->id)
            ->firstOrFail();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::ChatOpened)
            ->where('issue_id', $issue->id)
            ->where('user_id', $owner->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Chat geopend', $notification->title);
        $this->assertStringContainsString($officer->username, $notification->body);
        $this->assertSame(
            "chat_opened:issue:{$issue->id}:chat:{$chat->id}:user:{$owner->id}",
            $notification->dedup_key,
        );
    }

    public function test_chat_opened_idempotent_when_already_open_does_not_notify(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->openChatContext();

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ])
            ->assertOk();

        $this->assertDatabaseCount('domain_notifications', 0);
    }

    public function test_chat_closed_notifies_chat_user(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/{$chat->id}/close")
            ->assertOk();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::ChatClosed)
            ->where('issue_id', $issue->id)
            ->where('user_id', $owner->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Chat gesloten', $notification->title);
        $this->assertStringContainsString($officer->username, $notification->body);
        $this->assertSame(
            "chat_closed:issue:{$issue->id}:chat:{$chat->id}:user:{$owner->id}",
            $notification->dedup_key,
        );
    }

    public function test_gesloten_bulk_close_sends_one_chat_closed_per_user(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::Resolved)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $ownerChat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $participantChat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/status", [
                'status' => IssueStatus::Closed->value,
            ])
            ->assertOk();

        $notifications = DomainNotification::query()
            ->where('type', NotificationType::ChatClosed)
            ->where('issue_id', $issue->id)
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$owner->id, $participant->id],
            $notifications->pluck('user_id')->all(),
        );

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::ChatClosed->value,
            'dedup_key' => "chat_closed:issue:{$issue->id}:chat:{$ownerChat->id}:user:{$owner->id}",
        ]);

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::ChatClosed->value,
            'dedup_key' => "chat_closed:issue:{$issue->id}:chat:{$participantChat->id}:user:{$participant->id}",
        ]);
    }

    public function test_chat_closed_dedup_key_prevents_duplicate_rows(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $notify = new NotifyChatClosed;

        $notify->notify($issue, $chat, $officer);
        $notify->notify($issue, $chat, $officer);

        $this->assertDatabaseCount('domain_notifications', 1);
    }

    public function test_system_message_does_not_create_new_message_notification(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $issue->update(['status' => IssueStatus::Resolved]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/status", [
                'status' => IssueStatus::Closed->value,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('domain_notifications', [
            'type' => NotificationType::NewMessage->value,
            'issue_id' => $issue->id,
        ]);

        $this->assertDatabaseHas('issue_messages', [
            'issue_chat_id' => $chat->id,
            'message_type' => IssueMessageType::System->value,
        ]);
    }

    public function test_resolution_store_notifies_participants(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->openChatContext();

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

        $notifications = DomainNotification::query()
            ->where('type', NotificationType::ResolutionPosted)
            ->where('issue_id', $issue->id)
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$owner->id, $participant->id],
            $notifications->pluck('user_id')->all(),
        );

        $notification = $notifications->first();
        $this->assertSame('Oplossing geplaatst', $notification->title);
        $this->assertStringContainsString($issue->title, $notification->body);
        $this->assertStringContainsString($officer->username, $notification->body);
    }

    public function test_resolution_update_notifies_participants_again(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->openChatContext();

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'joined_via' => JoinedVia::Creator,
        ]);

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

        $this->assertSame(
            2,
            DomainNotification::query()
                ->where('type', NotificationType::ResolutionPosted)
                ->where('issue_id', $issue->id)
                ->count(),
        );
    }
}
