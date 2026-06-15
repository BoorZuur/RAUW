<?php

namespace Tests\Feature;

use App\Enums\ChatStatus;
use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class IssueChatMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(object $actor): array
    {
        Auth::forgetGuards();

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

    public function test_officer_can_send_message_in_open_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'chat' => $chat] = $this->openChatContext();

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chat->id}/messages",
            ['content' => 'Hello from officer'],
            $this->authHeaders($officer),
        )
            ->assertCreated()
            ->assertJsonPath('content', 'Hello from officer')
            ->assertJsonPath('sender_type', IssueMessageSenderType::Officer->value);
    }

    public function test_owner_can_send_message_in_open_chat(): void
    {
        ['issue' => $issue, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chat->id}/messages",
            ['content' => 'Hello from citizen'],
            $this->authHeaders($owner),
        )
            ->assertCreated()
            ->assertJsonPath('content', 'Hello from citizen')
            ->assertJsonPath('sender_type', IssueMessageSenderType::User->value);
    }

    public function test_send_message_rejects_closed_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $chat->update(['status' => ChatStatus::Closed]);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages", [
                'content' => 'Should fail',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'chat_closed');

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages", [
                'content' => 'Should also fail',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'chat_closed');
    }

    public function test_participants_can_read_messages_in_closed_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::Officer,
            'officer_id' => $officer->id,
            'user_id' => null,
            'content' => 'Archived message',
        ]);

        $chat->update(['status' => ChatStatus::Closed]);

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Archived message');

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_mark_read_rejects_closed_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::Officer,
            'officer_id' => $officer->id,
            'user_id' => null,
            'content' => 'Unread',
            'is_read' => false,
        ]);

        $chat->update(['status' => ChatStatus::Closed]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages/mark-read")
            ->assertStatus(422)
            ->assertJsonPath('code', 'chat_closed');
    }

    public function test_mark_read_marks_other_partys_unread_messages_in_open_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner, 'chat' => $chat] = $this->openChatContext();

        $message = IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::Officer,
            'officer_id' => $officer->id,
            'user_id' => null,
            'content' => 'Please read me',
            'is_read' => false,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages/mark-read")
            ->assertOk()
            ->assertJsonPath('marked_read', 1);

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_anonymous_owner_sender_is_redacted_in_messages(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create(['username' => 'real-owner']);
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::InProgress)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
            'is_anonymous' => true,
            'anonymous_alias' => 'Anoniem#000001',
        ]);

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::User,
            'user_id' => $owner->id,
            'officer_id' => null,
            'content' => 'Anonymous hello',
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.sender.is_anonymous', true)
            ->assertJsonPath('data.0.sender.display_name', 'Anoniem#000001')
            ->assertJsonMissingPath('data.0.sender.username');
    }

    public function test_system_messages_expose_meta_and_sender_type(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'chat' => $chat] = $this->openChatContext();

        IssueMessage::factory()->system()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/chats/{$chat->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.message_type', IssueMessageType::System->value)
            ->assertJsonPath('data.0.sender.type', IssueMessageSenderType::System->value)
            ->assertJsonPath('data.0.meta.code', 'chat_closed_status_gesloten');
    }
}
