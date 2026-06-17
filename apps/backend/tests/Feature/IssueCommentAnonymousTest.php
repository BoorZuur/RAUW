<?php

namespace Tests\Feature;

use App\Enums\JoinedVia;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Hub;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueCommentAnonymity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class IssueCommentAnonymousTest extends TestCase
{
    use RefreshDatabase;

    private IssueCommentAnonymity $anonymity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
        Config::set('notifications.enabled', true);
        $this->anonymity = new IssueCommentAnonymity;
    }

    /**
     * @return array{category: Category, district: District}
     */
    private function baseFixtures(): array
    {
        return [
            'category' => Category::factory()->withDepartments()->create(),
            'district' => District::factory()->create(['is_active' => true]),
        ];
    }

    /**
     * @param  array<string, mixed>  $issueOverrides
     * @return array{owner: User, issue: Issue, category: Category, district: District}
     */
    private function anonymousIssueContext(array $issueOverrides = []): array
    {
        ['category' => $category, 'district' => $district] = $this->baseFixtures();
        $owner = User::factory()->create([
            'is_active' => true,
            'username' => 'issue_owner',
        ]);

        $issue = Issue::factory()->create(array_merge([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'is_anonymous' => true,
            'anonymous_alias' => 'Melder#AB12',
        ], $issueOverrides));

        return compact('owner', 'issue', 'category', 'district');
    }

    /**
     * @param  array<string, mixed>  $issueOverrides
     * @return array{owner: User, issue: Issue, category: Category, district: District}
     */
    private function publicIssueContext(array $issueOverrides = []): array
    {
        ['category' => $category, 'district' => $district] = $this->baseFixtures();
        $owner = User::factory()->create([
            'is_active' => true,
            'username' => 'public_owner',
        ]);

        $issue = Issue::factory()->create(array_merge([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'is_anonymous' => false,
            'anonymous_alias' => null,
        ], $issueOverrides));

        return compact('owner', 'issue', 'category', 'district');
    }

    private function activeOfficerForDistrict(District $district): Officer
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district->update(['hub_id' => $hub->id]);

        return Officer::factory()->withDistricts([$district])->create([
            'hub_id' => $hub->id,
            'hub_active_until' => now()->addHour(),
            'is_active' => true,
            'username' => 'assigned_officer',
        ]);
    }

    /** T1: Owner default on anonymous issue → alias */
    public function test_owner_comment_on_anonymous_issue_uses_alias(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Mijn reactie',
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', true)
            ->assertJsonPath('author.is_anonymous', true)
            ->assertJsonPath('author.display_name', 'Melder#AB12')
            ->assertJsonMissingPath('author.username')
            ->assertJsonMissingPath('author.id');

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'is_anonymous' => true,
            'content' => 'Mijn reactie',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.author.is_anonymous', true)
            ->assertJsonPath('data.0.author.display_name', 'Melder#AB12');
    }

    /** T2: Owner explicit identified → username */
    public function test_owner_explicit_identified_comment_returns_username(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Identified reply',
                'is_anonymous' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', false)
            ->assertJsonPath('author.is_anonymous', false)
            ->assertJsonPath('author.username', 'issue_owner')
            ->assertJsonPath('author.display_name', 'issue_owner')
            ->assertJsonPath('author.id', $owner->id);

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'is_anonymous' => false,
        ]);
    }

    /** T3: Anonymous participant default → alias */
    public function test_anonymous_participant_default_uses_alias(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();
        $participant = User::factory()->create([
            'is_active' => true,
            'username' => 'anon_participant',
        ]);

        $participantRow = IssueParticipant::factory()->manual()->anonymous()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $expectedAlias = 'Deelnemer#'.str_pad((string) $participantRow->id, 6, '0', STR_PAD_LEFT);

        $this->actingAs($participant, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Participant reply',
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', true)
            ->assertJsonPath('author.display_name', $expectedAlias)
            ->assertJsonMissingPath('author.username');

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'is_anonymous' => true,
        ]);
    }

    /** T4: Participant override off → username */
    public function test_anonymous_participant_override_off_returns_username(): void
    {
        ['issue' => $issue] = $this->anonymousIssueContext();
        $participant = User::factory()->create([
            'is_active' => true,
            'username' => 'identified_participant',
        ]);

        IssueParticipant::factory()->manual()->anonymous()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);

        $this->actingAs($participant, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Public participant reply',
                'is_anonymous' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', false)
            ->assertJsonPath('author.is_anonymous', false)
            ->assertJsonPath('author.username', 'identified_participant')
            ->assertJsonPath('author.id', $participant->id);
    }

    /** T5: Public issue non-participant toggle on → hash Deelnemer#, no id/username */
    public function test_public_issue_non_participant_anonymous_comment_uses_hash_alias(): void
    {
        ['issue' => $issue] = $this->publicIssueContext();
        $commenter = User::factory()->create([
            'is_active' => true,
            'username' => 'random_commenter',
        ]);

        $expectedAlias = $this->anonymity->hashAlias($commenter->id, $issue->id);

        $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Anonymous outsider',
                'is_anonymous' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', true)
            ->assertJsonPath('author.is_anonymous', true)
            ->assertJsonPath('author.display_name', $expectedAlias)
            ->assertJsonMissingPath('author.username')
            ->assertJsonMissingPath('author.id');

        $this->assertStringStartsWith('Deelnemer#', $expectedAlias);
    }

    /** T5b: Same user second anonymous comment → same hash alias */
    public function test_same_user_second_anonymous_comment_uses_same_hash_alias(): void
    {
        ['issue' => $issue] = $this->publicIssueContext();
        $commenter = User::factory()->create(['is_active' => true]);

        $expectedAlias = $this->anonymity->hashAlias($commenter->id, $issue->id);

        $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'First anonymous',
                'is_anonymous' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('author.display_name', $expectedAlias);

        $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Second anonymous',
                'is_anonymous' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('author.display_name', $expectedAlias);
    }

    /** T6: Public issue default off → username */
    public function test_public_issue_default_off_returns_username(): void
    {
        ['issue' => $issue] = $this->publicIssueContext();
        $commenter = User::factory()->create([
            'is_active' => true,
            'username' => 'public_commenter',
        ]);

        $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Open comment',
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', false)
            ->assertJsonPath('author.is_anonymous', false)
            ->assertJsonPath('author.username', 'public_commenter')
            ->assertJsonPath('author.id', $commenter->id);
    }

    /** T7: Officer on anonymous issue → officer username */
    public function test_officer_comment_on_anonymous_issue_uses_officer_username(): void
    {
        ['issue' => $issue, 'district' => $district] = $this->anonymousIssueContext();
        $officer = $this->activeOfficerForDistrict($district);
        $issue->update(['assigned_officer_id' => $officer->id]);

        $this->actingAs($officer, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Officer update',
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', false)
            ->assertJsonPath('author.is_anonymous', false)
            ->assertJsonPath('author.username', 'assigned_officer')
            ->assertJsonPath('author.id', $officer->id);

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
            'is_anonymous' => false,
        ]);
    }

    /** T8: Manager on anonymous issue → manager username */
    public function test_manager_comment_on_anonymous_issue_uses_manager_username(): void
    {
        ['issue' => $issue, 'district' => $district] = $this->anonymousIssueContext();
        $manager = Manager::factory()->withDistricts([$district])->create([
            'is_active' => true,
            'username' => 'district_manager',
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Manager note',
            ])
            ->assertCreated()
            ->assertJsonPath('is_anonymous', false)
            ->assertJsonPath('author.is_anonymous', false)
            ->assertJsonPath('author.username', 'district_manager')
            ->assertJsonPath('author.id', $manager->id);
    }

    /** T9: List redaction user + officer viewer */
    public function test_list_redaction_same_for_user_and_officer_viewer(): void
    {
        ['owner' => $owner, 'issue' => $issue, 'district' => $district] = $this->anonymousIssueContext();
        $viewer = User::factory()->create(['is_active' => true]);
        $officer = $this->activeOfficerForDistrict($district);
        $issue->update(['assigned_officer_id' => $officer->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Hidden identity',
            ])
            ->assertCreated();

        $expectedAuthor = [
            'is_anonymous' => true,
            'display_name' => 'Melder#AB12',
        ];

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.author', $expectedAuthor);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.author', $expectedAuthor);
    }

    /** T10: Notification actor name uses alias */
    public function test_notification_uses_alias_when_anonymous(): void
    {
        ['owner' => $owner, 'issue' => $issue, 'district' => $district] = $this->anonymousIssueContext();
        $officer = $this->activeOfficerForDistrict($district);
        $issue->update(['assigned_officer_id' => $officer->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Notify anonymously',
            ])
            ->assertCreated();

        $notification = DomainNotification::query()
            ->where('type', NotificationType::NewComment)
            ->where('issue_id', $issue->id)
            ->where('officer_id', $officer->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Melder#AB12', $notification->body);
        $this->assertStringNotContainsString($owner->username, $notification->body);
    }

    /** T11 + issue resource default_comment_is_anonymous */
    public function test_can_update_on_own_anonymous_comment_and_issue_default_toggle(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Editable anonymous',
            ])
            ->assertCreated();

        $commentId = $response->json('id');

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/issues/{$issue->id}")
            ->assertOk()
            ->assertJsonPath('default_comment_is_anonymous', true);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.can_update', true)
            ->assertJsonPath('data.0.can_delete', true)
            ->assertJsonPath('data.0.id', $commentId);
    }

    public function test_issue_default_comment_is_anonymous_false_for_public_non_participant(): void
    {
        ['issue' => $issue] = $this->publicIssueContext();
        $outsider = User::factory()->create(['is_active' => true]);

        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/issues/{$issue->id}")
            ->assertOk()
            ->assertJsonPath('default_comment_is_anonymous', false);
    }

    /** T12: can_update false for other user */
    public function test_can_update_false_for_other_user(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();
        $otherUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Someone elses comment',
            ])
            ->assertCreated();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.can_update', false)
            ->assertJsonPath('data.0.can_delete', false);
    }

    /** T13: PATCH own comment content only, is_anonymous unchanged */
    public function test_patch_own_comment_leaves_is_anonymous_unchanged(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();

        $comment = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'Original text',
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/issues/{$issue->id}/comments/{$comment['id']}", [
                'content' => 'Updated text',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Updated text')
            ->assertJsonPath('is_anonymous', true)
            ->assertJsonPath('author.display_name', 'Melder#AB12');

        $this->assertDatabaseHas('issue_comments', [
            'id' => $comment['id'],
            'content' => 'Updated text',
            'is_anonymous' => true,
        ]);
    }

    /** T14: DELETE own comment */
    public function test_delete_own_comment(): void
    {
        ['owner' => $owner, 'issue' => $issue] = $this->anonymousIssueContext();

        $commentId = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/issues/{$issue->id}/comments", [
                'content' => 'To be removed',
            ])
            ->assertCreated()
            ->json('id');

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/issues/{$issue->id}/comments/{$commentId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('issue_comments', [
            'id' => $commentId,
        ]);
    }
}
