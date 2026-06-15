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

class IssueChatReassignmentTest extends TestCase
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
     * @return array{issue: Issue, district: District, officerA: Officer, officerB: Officer, owner: User}
     */
    private function sharedDistrictOfficers(): array
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();

        $officerA = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $officerB = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $issue = Issue::factory()->withStatus(IssueStatus::InProgress)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officerA->id,
        ]);

        return compact('issue', 'district', 'officerA', 'officerB', 'owner');
    }

    public function test_assign_self_returns_409_when_issue_assigned_to_another_officer(): void
    {
        ['issue' => $issue, 'officerB' => $officerB] = $this->sharedDistrictOfficers();

        $this->withHeaders($this->authHeaders($officerB))
            ->postJson("/api/issues/{$issue->id}/assign-self")
            ->assertStatus(409)
            ->assertJsonPath('code', 'issue_already_assigned');
    }

    public function test_reassignment_flow_allows_successor_to_reopen_read_history_and_send(): void
    {
        ['issue' => $issue, 'officerA' => $officerA, 'officerB' => $officerB, 'owner' => $owner] = $this->sharedDistrictOfficers();

        $openResponse = $this->patchJson(
            "/api/issues/{$issue->id}/chats/open",
            ['user_id' => $owner->id],
            $this->authHeaders($officerA),
        );

        $openResponse->assertCreated();
        $chatId = $openResponse->json('id');

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chatId}/messages",
            ['content' => 'Message from officer A'],
            $this->authHeaders($officerA),
        )->assertCreated();

        $this->postJson(
            "/api/issues/{$issue->id}/unassign-self",
            [],
            $this->authHeaders($officerA),
        )
            ->assertOk()
            ->assertJsonPath('assigned_officer_id', null);

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'assigned_officer_id' => null,
        ]);

        $this->assertDatabaseHas('issue_chats', [
            'id' => $chatId,
            'status' => ChatStatus::Closed->value,
        ]);

        $this->postJson(
            "/api/issues/{$issue->id}/assign-self",
            [],
            $this->authHeaders($officerB),
        )
            ->assertOk()
            ->assertJsonPath('assigned_officer_id', $officerB->id);

        $reopenResponse = $this->patchJson(
            "/api/issues/{$issue->id}/chats/open",
            ['user_id' => $owner->id],
            $this->authHeaders($officerB),
        );

        $reopenResponse->assertOk()
            ->assertJsonPath('id', $chatId)
            ->assertJsonPath('status', ChatStatus::Open->value);

        $this->getJson(
            "/api/issues/{$issue->id}/chats/{$chatId}/messages",
            $this->authHeaders($officerB),
        )
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Message from officer A');

        $this->postJson(
            "/api/issues/{$issue->id}/chats/{$chatId}/messages",
            ['content' => 'Message from officer B'],
            $this->authHeaders($officerB),
        )
            ->assertCreated()
            ->assertJsonPath('content', 'Message from officer B');
    }

    public function test_gesloten_status_closes_open_chats_with_system_message(): void
    {
        ['issue' => $issue, 'officerA' => $officer, 'owner' => $owner] = $this->sharedDistrictOfficers();

        $issue->update(['status' => IssueStatus::Resolved]);

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/status", [
                'status' => IssueStatus::Closed->value,
            ])
            ->assertOk()
            ->assertJsonPath('status', IssueStatus::Closed->value);

        $chat->refresh();
        $this->assertSame(ChatStatus::Closed, $chat->status);

        $this->assertDatabaseHas('issue_messages', [
            'issue_chat_id' => $chat->id,
            'message_type' => IssueMessageType::System->value,
            'sender_type' => IssueMessageSenderType::System->value,
            'content' => 'Chat gesloten omdat de melding is afgerond.',
        ]);
    }

    public function test_unassign_closes_open_chats_without_system_message(): void
    {
        ['issue' => $issue, 'officerA' => $officer, 'owner' => $owner] = $this->sharedDistrictOfficers();

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        IssueMessage::factory()->create([
            'issue_id' => $issue->id,
            'issue_chat_id' => $chat->id,
            'sender_type' => IssueMessageSenderType::Officer,
            'officer_id' => $officer->id,
            'user_id' => null,
            'content' => 'Regular message',
            'message_type' => IssueMessageType::Message,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/unassign-self")
            ->assertOk();

        $chat->refresh();
        $this->assertSame(ChatStatus::Closed, $chat->status);

        $this->assertDatabaseMissing('issue_messages', [
            'issue_chat_id' => $chat->id,
            'message_type' => IssueMessageType::System->value,
        ]);

        $this->assertDatabaseHas('issue_messages', [
            'issue_chat_id' => $chat->id,
            'content' => 'Regular message',
            'message_type' => IssueMessageType::Message->value,
        ]);
    }
}
