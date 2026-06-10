<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCreateDuplicateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
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

    public function test_duplicate_create_produces_hidden_child_with_counters_and_participant(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 0,
            'participant_count' => 1,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district, [
                'duplicate_of_id' => $canonical->id,
            ]));

        $response->assertCreated()
            ->assertJsonPath('visibility', Visibility::Hidden->value)
            ->assertJsonPath('duplicate_of_id', $canonical->id)
            ->assertJsonPath('title', 'Broken street light');

        $child = Issue::query()->findOrFail($response->json('id'));
        $canonical->refresh();

        $this->assertSame(Visibility::Hidden, $child->visibility);
        $this->assertSame($canonical->id, $child->duplicate_of_id);
        $this->assertSame(1, $canonical->duplicate_count);
        $this->assertSame(2, $canonical->participant_count);

        $this->assertDatabaseHas('issue_participants', [
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
            'joined_via' => JoinedVia::Duplicate->value,
            'via_issue_id' => $child->id,
        ]);
    }

    public function test_duplicate_create_rejects_non_matchable_canonical_status(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Resolved)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district, [
                'duplicate_of_id' => $canonical->id,
            ]));

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'issue_not_matchable');
    }

    public function test_duplicate_create_rejects_self_duplicate(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district, [
                'duplicate_of_id' => $canonical->id,
            ]));

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'cannot_duplicate_self');
    }

    public function test_normal_create_adds_creator_participant_and_counter(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $response = $this->withHeaders($this->authHeaders($author))
            ->postJson('/api/issues', $this->storePayload($category, $district));

        $response->assertCreated()
            ->assertJsonPath('duplicate_count', 0);

        $issueId = $response->json('id');

        $this->assertDatabaseHas('issue_participants', [
            'issue_id' => $issueId,
            'user_id' => $author->id,
            'joined_via' => JoinedVia::Creator->value,
            'via_issue_id' => null,
        ]);

        $this->assertSame(
            1,
            IssueParticipant::query()->where('issue_id', $issueId)->count(),
        );
    }
}
