<?php

namespace Tests\Feature;

use App\Enums\ChatStatus;
use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueParticipant;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueChatTest extends TestCase
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
     * @return array{issue: Issue, district: District, officer: Officer, owner: User, category: Category}
     */
    private function assignedIssueSetup(IssueStatus $status = IssueStatus::InProgress, bool $officerHasShift = true): array
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $owner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => $officerHasShift ? now()->addHour() : null,
        ]);

        $issue = Issue::factory()->withStatus($status)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        return compact('issue', 'district', 'officer', 'owner', 'category');
    }

    public function test_officer_can_open_chat_with_issue_owner(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup();

        $response = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', ChatStatus::Open->value)
            ->assertJsonPath('user_id', $owner->id)
            ->assertJsonPath('issue_id', $issue->id)
            ->assertJsonPath('opened_by_officer_id', $officer->id);

        $this->assertDatabaseHas('issue_chats', [
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'status' => ChatStatus::Open->value,
        ]);
    }

    public function test_officer_can_open_chat_with_eligible_participant(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedIssueSetup();
        $participant = User::factory()->create();

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $participant->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', ChatStatus::Open->value)
            ->assertJsonPath('user_id', $participant->id);
    }

    public function test_open_chat_rejects_gesloten_issue(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup(IssueStatus::Closed);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'issue_closed');
    }

    public function test_open_chat_rejects_ineligible_user(): void
    {
        ['issue' => $issue, 'officer' => $officer] = $this->assignedIssueSetup();
        $outsider = User::factory()->create();

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $outsider->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'chat_user_not_eligible');
    }

    public function test_open_chat_is_idempotent_when_already_open(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup();

        $first = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ]);

        $first->assertCreated();
        $chatId = $first->json('id');

        $second = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ]);

        $second->assertOk()
            ->assertJsonPath('id', $chatId)
            ->assertJsonPath('status', ChatStatus::Open->value);

        $this->assertSame(
            1,
            IssueChat::query()
                ->where('issue_id', $issue->id)
                ->where('user_id', $owner->id)
                ->count(),
        );
    }

    public function test_officer_can_open_parallel_chats_with_two_users(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup();
        $participant = User::factory()->create();

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $ownerChat = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", ['user_id' => $owner->id]);

        $participantChat = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", ['user_id' => $participant->id]);

        $ownerChat->assertCreated()->assertJsonPath('user_id', $owner->id);
        $participantChat->assertCreated()->assertJsonPath('user_id', $participant->id);

        $this->assertNotSame($ownerChat->json('id'), $participantChat->json('id'));
        $this->assertSame(2, IssueChat::query()->where('issue_id', $issue->id)->count());
    }

    public function test_non_participant_user_cannot_list_chats(): void
    {
        ['issue' => $issue] = $this->assignedIssueSetup();
        $outsider = User::factory()->create();

        $this->withHeaders($this->authHeaders($outsider))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertForbidden()
            ->assertJsonPath('code', 'not_chat_participant');
    }

    public function test_manager_cannot_list_chats(): void
    {
        ['issue' => $issue, 'district' => $district] = $this->assignedIssueSetup();
        $manager = Manager::factory()->withDistricts([$district])->create();

        $this->withHeaders($this->authHeaders($manager))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertForbidden();
    }

    public function test_non_assignee_officer_cannot_list_chats(): void
    {
        ['issue' => $issue, 'district' => $district, 'officer' => $assignedOfficer] = $this->assignedIssueSetup();
        $otherOfficer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $this->assertNotSame($assignedOfficer->id, $otherOfficer->id);

        $this->withHeaders($this->authHeaders($otherOfficer))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertForbidden()
            ->assertJsonPath('code', 'not_assigned_officer');
    }

    public function test_eligible_user_with_no_chats_gets_empty_list(): void
    {
        ['issue' => $issue, 'owner' => $owner] = $this->assignedIssueSetup();

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_assignee_officer_can_list_all_chats(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup();
        $participant = User::factory()->create();

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_officer_can_list_chats_without_active_shift(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup(officerHasShift: false);

        IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/chats")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_officer_cannot_open_chat_without_active_shift(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup(officerHasShift: false);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/open", [
                'user_id' => $owner->id,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'hub_active_required');
    }

    public function test_open_chat_via_duplicate_child_route_resolves_to_canonical(): void
    {
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $canonicalOwner = User::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $canonical = Issue::factory()->withStatus(IssueStatus::InProgress)->create([
            'user_id' => $canonicalOwner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $childOwner = User::factory()->create();
        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $childOwner->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $childOwner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$child->id}/chats/open", [
                'user_id' => $childOwner->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('issue_id', $canonical->id)
            ->assertJsonPath('user_id', $childOwner->id);

        $this->assertDatabaseHas('issue_chats', [
            'issue_id' => $canonical->id,
            'user_id' => $childOwner->id,
            'status' => ChatStatus::Open->value,
        ]);
    }

    public function test_officer_can_close_open_chat(): void
    {
        ['issue' => $issue, 'officer' => $officer, 'owner' => $owner] = $this->assignedIssueSetup();

        $chat = IssueChat::factory()->open()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'opened_by_officer_id' => $officer->id,
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/chats/{$chat->id}/close")
            ->assertOk()
            ->assertJsonPath('status', ChatStatus::Closed->value)
            ->assertJsonPath('closed_by_officer_id', $officer->id);
    }
}
