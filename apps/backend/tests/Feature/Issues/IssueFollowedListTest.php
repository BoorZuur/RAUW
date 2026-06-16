<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueFollowedListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    /**
     * @return list<int>
     */
    private function followedIds(User $user): array
    {
        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?followed=1');

        $response->assertOk();

        return collect($response->json('data'))->pluck('id')->all();
    }

    public function test_manual_follow_returns_canonical_in_followed_list(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $ids = $this->followedIds($user);

        $this->assertContains($canonical->id, $ids);
    }

    public function test_duplicate_child_returns_child_not_canonical(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $user->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $ids = $this->followedIds($user);

        $this->assertContains($child->id, $ids);
        $this->assertNotContains($canonical->id, $ids);
    }

    public function test_child_and_participation_returns_child_only(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $user->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $ids = $this->followedIds($user);

        $this->assertSame([$child->id], $ids);
    }

    public function test_own_canonical_report_is_excluded_from_followed_list(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $ownCanonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $ownCanonical->id,
            'user_id' => $user->id,
        ]);

        $ids = $this->followedIds($user);

        $this->assertNotContains($ownCanonical->id, $ids);
    }

    public function test_left_participation_is_excluded_from_followed_list(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $participant = IssueParticipant::factory()->manual()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $participant->delete();

        $ids = $this->followedIds($user);

        $this->assertNotContains($canonical->id, $ids);
    }

    public function test_deleted_child_with_remaining_participation_returns_canonical(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $user->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $child->delete();

        $ids = $this->followedIds($user);

        $this->assertContains($canonical->id, $ids);
        $this->assertNotContains($child->id, $ids);
    }

    public function test_officer_cannot_use_followed_filter(): void
    {
        $district = District::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson('/api/issues?followed=1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['followed']);
    }

    public function test_followed_and_mine_are_mutually_exclusive(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?followed=1&mine=1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['followed', 'mine']);
    }
}
