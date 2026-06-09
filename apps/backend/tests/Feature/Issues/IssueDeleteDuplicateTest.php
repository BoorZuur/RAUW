<?php

namespace Tests\Feature\Issues;

use App\Enums\IssueStatus;
use App\Enums\JoinedVia;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueDeleteDuplicateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_deleting_duplicate_child_decrements_duplicate_count(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 1,
            'participant_count' => 2,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $author->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($author))
            ->deleteJson("/api/issues/{$child->id}", [
                'leave_participation' => false,
            ]);

        $response->assertNoContent();

        $canonical->refresh();

        $this->assertSame(0, $canonical->duplicate_count);
        $this->assertDatabaseMissing('issues', ['id' => $child->id]);
    }

    public function test_leave_participation_false_keeps_canonical_participation(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 1,
            'participant_count' => 2,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $author->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
        ]);

        $this->withHeaders($this->authHeaders($author))
            ->deleteJson("/api/issues/{$child->id}", [
                'leave_participation' => false,
            ])
            ->assertNoContent();

        $canonical->refresh();

        $this->assertSame(2, $canonical->participant_count);
        $this->assertDatabaseHas('issue_participants', [
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
            'joined_via' => JoinedVia::Duplicate->value,
        ]);
    }

    public function test_leave_participation_true_removes_canonical_participation(): void
    {
        $author = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'duplicate_count' => 1,
            'participant_count' => 2,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $author->id,
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
        ]);

        $this->withHeaders($this->authHeaders($author))
            ->deleteJson("/api/issues/{$child->id}", [
                'leave_participation' => true,
            ])
            ->assertNoContent();

        $canonical->refresh();

        $this->assertSame(1, $canonical->participant_count);
        $this->assertDatabaseMissing('issue_participants', [
            'issue_id' => $canonical->id,
            'user_id' => $author->id,
        ]);
    }
}
