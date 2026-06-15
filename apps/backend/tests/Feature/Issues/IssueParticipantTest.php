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

class IssueParticipantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_join_is_idempotent(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'participant_count' => 1,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        $first = $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/issues/{$canonical->id}/join");

        $first->assertCreated()
            ->assertJsonPath('participant_count', 2);

        $canonical->refresh();
        $this->assertSame(2, $canonical->participant_count);

        $second = $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/issues/{$canonical->id}/join");

        $second->assertOk()
            ->assertJsonPath('participant_count', 2);

        $canonical->refresh();
        $this->assertSame(2, $canonical->participant_count);
        $this->assertSame(
            1,
            IssueParticipant::query()
                ->where('issue_id', $canonical->id)
                ->where('user_id', $user->id)
                ->count(),
        );
    }

    public function test_leave_removes_participation(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'participant_count' => 2,
        ]);

        IssueParticipant::factory()->creator()->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->deleteJson("/api/issues/{$canonical->id}/leave");

        $response->assertNoContent();

        $canonical->refresh();

        $this->assertSame(1, $canonical->participant_count);
        $this->assertDatabaseMissing('issue_participants', [
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_join_on_duplicate_child_is_rejected(): void
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

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/issues/{$child->id}/join");

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'cannot_join_duplicate_child');
    }
}
