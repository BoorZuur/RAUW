<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueSimilarCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function similarCheckPayload(Category $category, District $district, array $overrides = []): array
    {
        return array_merge([
            'district_id' => $district->id,
            'category_id' => $category->id,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_splits_own_matches_from_linkable_matches(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $ownIssue = Issue::factory()->create([
            'user_id' => $actor->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ]);

        $otherIssue = Issue::factory()->create([
            'user_id' => $other->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $district));

        $response->assertOk();

        $ownIds = collect($response->json('own_matches'))->pluck('id')->all();
        $matchIds = collect($response->json('matches'))->pluck('id')->all();

        $this->assertContains($ownIssue->id, $ownIds);
        $this->assertContains($otherIssue->id, $matchIds);
        $this->assertNotContains($ownIssue->id, $matchIds);
        $this->assertNotContains($otherIssue->id, $ownIds);

        $ownRow = collect($response->json('own_matches'))->firstWhere('id', $ownIssue->id);
        $otherRow = collect($response->json('matches'))->firstWhere('id', $otherIssue->id);

        $this->assertFalse($ownRow['linkable']);
        $this->assertTrue($otherRow['linkable']);
    }

    public function test_returns_at_most_five_matches_total(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        Issue::factory()->count(6)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $district));

        $response->assertOk();

        $total = count($response->json('own_matches')) + count($response->json('matches'));
        $this->assertLessThanOrEqual(5, $total);
    }

    public function test_excludes_non_matchable_statuses(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $openIssue = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'postal_code' => '3011AA',
        ]);

        Issue::factory()->withStatus(IssueStatus::Resolved)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'postal_code' => '3011AA',
        ]);

        Issue::factory()->withStatus(IssueStatus::Closed)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'postal_code' => '3011AA',
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $district));

        $response->assertOk();

        $allIds = collect($response->json('own_matches'))
            ->merge($response->json('matches'))
            ->pluck('id')
            ->all();

        $this->assertContains($openIssue->id, $allIds);
        $this->assertCount(1, $allIds);
    }

    public function test_filters_by_district(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $districtA = District::factory()->create();
        $districtB = District::factory()->create();

        Issue::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $districtA->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $districtB));

        $response->assertOk()
            ->assertJsonPath('own_matches', [])
            ->assertJsonPath('matches', []);
    }

    public function test_orders_results_by_score_descending(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $otherCategory = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $highScore = Issue::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
            'title' => 'High score candidate',
        ]);

        $mediumScore = Issue::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '9999ZZ',
            'latitude' => null,
            'longitude' => null,
            'title' => 'Medium score candidate',
        ]);

        $lowScore = Issue::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $otherCategory->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '9999ZZ',
            'latitude' => null,
            'longitude' => null,
            'title' => 'Low score candidate',
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $district));

        $response->assertOk();

        $orderedIds = collect($response->json('matches'))->pluck('id')->all();

        $this->assertSame([$highScore->id, $mediumScore->id, $lowScore->id], $orderedIds);

        $scores = collect($response->json('matches'))->pluck('score')->all();
        $this->assertGreaterThan($scores[1], $scores[0]);
        $this->assertGreaterThan($scores[2], $scores[1]);
    }

    public function test_includes_first_attachment_on_match(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'status' => IssueStatus::Open,
            'postal_code' => '3011AA',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ]);

        $attachment = IssueAttachment::factory()->create([
            'issue_id' => $issue->id,
            'original_name' => 'foto.jpg',
            'file_type' => 'image/jpeg',
        ]);

        IssueAttachment::factory()->create([
            'issue_id' => $issue->id,
            'original_name' => 'extra.jpg',
            'file_type' => 'image/jpeg',
        ]);

        $response = $this->withHeaders($this->authHeaders($actor))
            ->postJson('/api/issues/similar-check', $this->similarCheckPayload($category, $district));

        $response->assertOk();

        $match = collect($response->json('matches'))->firstWhere('id', $issue->id);

        $this->assertNotNull($match);
        $this->assertCount(1, $match['attachments']);
        $this->assertSame($attachment->id, $match['attachments'][0]['id']);
        $this->assertSame('foto.jpg', $match['attachments'][0]['original_name']);
        $this->assertStringContainsString(
            "/api/issues/{$issue->id}/attachments/{$attachment->id}/download",
            $match['attachments'][0]['download_url'],
        );
    }
}
