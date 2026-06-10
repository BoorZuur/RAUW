<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueDuplicatesListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    public function test_officer_can_list_duplicate_children(): void
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
            'title' => 'Duplicate child title',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$canonical->id}/duplicates");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $child->id)
            ->assertJsonPath('data.0.title', 'Duplicate child title');
    }

    public function test_user_cannot_list_duplicate_children(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/issues/{$canonical->id}/duplicates")
            ->assertForbidden();
    }

    public function test_duplicate_child_target_returns_not_canonical(): void
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

        $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$child->id}/duplicates")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'issue_not_canonical');
    }
}
