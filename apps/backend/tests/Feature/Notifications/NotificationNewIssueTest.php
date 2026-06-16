<?php

namespace Tests\Feature\Notifications;

use App\Enums\IssueStatus;
use App\Enums\NotificationType;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Department;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationNewIssueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('notifications.enabled', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function storePayload(Category $category, District $district, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Broken street light',
            'content' => 'The lamp on the corner is out.',
            'category_id' => $category->id,
            'district_id' => $district->id,
            'postal_code' => '3011AA',
            'address' => 'Coolsingel 1',
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_canonical_issue_create_notifies_matching_district_officers(): void
    {
        $author = User::factory()->create();
        $district = District::factory()->create();
        $issueDepartment = Department::factory()->create();
        $category = Category::factory()->withDepartments($issueDepartment)->create();

        $matchingOfficer = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$issueDepartment])
            ->create();

        Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([Department::factory()->create()])
            ->create();

        $response = $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district));

        $response->assertCreated();

        $issueId = $response->json('id');

        $this->assertDatabaseHas('domain_notifications', [
            'type' => NotificationType::NewIssue->value,
            'issue_id' => $issueId,
            'officer_id' => $matchingOfficer->id,
            'recipient_type' => 'officer',
            'actor_type' => 'user',
            'actor_id' => $author->id,
        ]);

        $this->assertDatabaseCount('domain_notifications', 1);
    }

    public function test_duplicate_issue_create_does_not_notify_officers(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $district = District::factory()->create();
        $category = Category::factory()->withDepartments()->create();

        Officer::factory()->withDistricts([$district])->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district, [
                'duplicate_of_id' => $canonical->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('visibility', Visibility::Hidden->value);

        $this->assertDatabaseCount('domain_notifications', 0);
    }

    public function test_disabled_notifications_config_skips_new_issue_writes(): void
    {
        Config::set('notifications.enabled', false);

        $author = User::factory()->create();
        $district = District::factory()->create();
        $category = Category::factory()->withDepartments()->create();

        Officer::factory()->withDistricts([$district])->create();

        $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district))
            ->assertCreated();

        $this->assertDatabaseCount('domain_notifications', 0);
    }

    public function test_falls_back_to_all_district_officers_when_department_intersection_empty(): void
    {
        $author = User::factory()->create();
        $district = District::factory()->create();
        $issueDepartment = Department::factory()->create();
        $officerDepartment = Department::factory()->create();
        $category = Category::factory()->withDepartments($issueDepartment)->create();

        $officerOne = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$officerDepartment])
            ->create();

        $officerTwo = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$officerDepartment])
            ->create();

        $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district))
            ->assertCreated();

        $notifications = DomainNotification::query()
            ->where('type', NotificationType::NewIssue)
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$officerOne->id, $officerTwo->id],
            $notifications->pluck('officer_id')->all(),
        );
    }

    public function test_visible_community_post_notifies_district_feed_followers(): void
    {
        $district = District::factory()->create(['is_active' => true]);
        $follower = User::factory()->create();
        $otherFollower = User::factory()->create();
        $follower->feedDistricts()->attach($district->id);
        $otherFollower->feedDistricts()->attach(District::factory()->create()->id);

        $officer = Officer::factory()
            ->withDistricts([$district])
            ->create([
                'is_active' => true,
                'hub_active_until' => now()->addHours(8),
            ]);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Neighborhood update',
                'content' => 'Road work next week.',
                'visibility' => Visibility::Visible->value,
            ])
            ->assertCreated();

        $notifications = DomainNotification::query()
            ->where('type', NotificationType::NewCommunityPost)
            ->get();

        $this->assertCount(1, $notifications);
        $this->assertSame($follower->id, $notifications->first()->user_id);
        $this->assertSame('Nieuw bericht in feed', $notifications->first()->title);
        $this->assertStringContainsString($officer->username, $notifications->first()->body);
    }

    public function test_hidden_community_post_store_does_not_notify_followers(): void
    {
        $district = District::factory()->create(['is_active' => true]);
        $follower = User::factory()->create();
        $follower->feedDistricts()->attach($district->id);

        $officer = Officer::factory()
            ->withDistricts([$district])
            ->create([
                'is_active' => true,
                'hub_active_until' => now()->addHours(8),
            ]);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Draft update',
                'content' => 'Not published yet.',
                'visibility' => Visibility::Hidden->value,
            ])
            ->assertCreated();

        $this->assertDatabaseCount('domain_notifications', 0);
    }
}
