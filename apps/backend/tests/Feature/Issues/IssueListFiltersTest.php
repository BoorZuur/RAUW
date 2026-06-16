<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueListFiltersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    public function test_user_browse_excludes_others_duplicate_children_by_default(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($viewer))
            ->getJson('/api/issues');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($canonical->id, $ids);
        $this->assertNotContains($child->id, $ids);
    }

    public function test_officer_can_include_duplicates(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
        ]);

        $without = $this->withHeaders($this->authHeaders($officer))
            ->getJson('/api/issues');

        $without->assertOk();
        $this->assertNotContains($child->id, collect($without->json('data'))->pluck('id')->all());

        $with = $this->withHeaders($this->authHeaders($officer))
            ->getJson('/api/issues?include_duplicates=1');

        $with->assertOk();
        $this->assertContains($child->id, collect($with->json('data'))->pluck('id')->all());
    }

    public function test_mine_includes_owned_duplicate_children(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $other->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?mine=1');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($child->id, $ids);
        $this->assertNotContains($canonical->id, $ids);
    }

    public function test_participating_lists_owned_duplicate_children_with_canonical_participation(): void
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

        $manualOnlyCanonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $manualOnlyCanonical->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?participating=1');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($child->id, $ids);
        $this->assertNotContains($canonical->id, $ids);
        $this->assertNotContains($manualOnlyCanonical->id, $ids);
    }

    public function test_mine_and_participating_are_mutually_exclusive(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?mine=1&participating=1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['mine', 'participating']);
    }

    public function test_exclude_mine_returns_only_others_issues(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $ownIssue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $othersIssue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $other->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?exclude_mine=1');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($ownIssue->id, $ids);
        $this->assertContains($othersIssue->id, $ids);
    }

    public function test_exclude_mine_also_excludes_owned_duplicate_children(): void
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

        $ownChild = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?exclude_mine=1');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($ownChild->id, $ids);
        $this->assertContains($canonical->id, $ids);
    }

    public function test_exclude_mine_and_mine_are_mutually_exclusive(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/issues?exclude_mine=1&mine=1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exclude_mine', 'mine']);
    }

    public function test_officer_cannot_use_exclude_mine_filter(): void
    {
        $district = District::factory()->create();
        $officer = Officer::factory()->withDistricts([$district])->create([
            'hub_active_until' => now()->addHour(),
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson('/api/issues?exclude_mine=1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exclude_mine']);
    }
}
