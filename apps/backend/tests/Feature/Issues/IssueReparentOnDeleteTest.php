<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IssueReparentOnDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_deleting_canonical_without_children_removes_issue(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/issues/{$canonical->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('issues', ['id' => $canonical->id]);
        $this->assertDatabaseMissing('issue_participants', ['issue_id' => $canonical->id]);
    }

    public function test_deleting_canonical_with_one_child_promotes_child(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 1,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/issues/{$canonical->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('issues', ['id' => $canonical->id]);

        $child->refresh();

        $this->assertNull($child->duplicate_of_id);
        $this->assertSame(0, $child->duplicate_count);
    }

    public function test_deleting_canonical_with_three_children_promotes_oldest_and_reparents_siblings(): void
    {
        Carbon::setTestNow('2026-06-01 12:00:00');

        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 3,
            'created_at' => '2026-05-01 10:00:00',
        ]);

        $oldest = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        $middle = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
            'created_at' => '2026-05-11 10:00:00',
        ]);

        $newest = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
            'created_at' => '2026-05-12 10:00:00',
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/issues/{$canonical->id}")
            ->assertNoContent();

        $oldest->refresh();
        $middle->refresh();
        $newest->refresh();

        $this->assertNull($oldest->duplicate_of_id);
        $this->assertSame(2, Issue::query()->where('duplicate_of_id', $oldest->id)->count());
        $this->assertSame($oldest->id, $middle->duplicate_of_id);
        $this->assertSame($oldest->id, $newest->duplicate_of_id);
        $this->assertDatabaseMissing('issues', ['id' => $canonical->id]);

        Carbon::setTestNow();
    }

    public function test_reparent_migrates_participants_to_promoted_issue(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 1,
            'participant_count' => 2,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $canonical->id,
            'user_id' => $participant->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
            'created_at' => now()->subDay(),
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/issues/{$canonical->id}")
            ->assertNoContent();

        $child->refresh();

        $this->assertDatabaseHas('issue_participants', [
            'issue_id' => $child->id,
            'user_id' => $participant->id,
        ]);
        $this->assertDatabaseMissing('issue_participants', [
            'issue_id' => $canonical->id,
        ]);
        $this->assertSame(
            2,
            IssueParticipant::query()->where('issue_id', $child->id)->count(),
        );
    }
}
