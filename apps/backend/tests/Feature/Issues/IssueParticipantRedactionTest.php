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

class IssueParticipantRedactionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$actor->createToken('test')->plainTextToken];
    }

    public function test_participant_sees_redacted_canonical_content(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'title' => 'Canonical title',
            'content' => 'Canonical content',
            'address' => 'Secret street 1',
            'postal_code' => '3011AA',
        ]);

        IssueParticipant::factory()->manual()->create([
            'issue_id' => $canonical->id,
            'user_id' => $participant->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($participant))
            ->getJson("/api/issues/{$canonical->id}");

        $response->assertOk()
            ->assertJsonPath('title', null)
            ->assertJsonPath('content', null)
            ->assertJsonPath('address', null)
            ->assertJsonPath('postal_code', null)
            ->assertJsonPath('status', IssueStatus::Open->value)
            ->assertJsonPath('is_participant', true)
            ->assertJsonPath('author.is_participant', true);
    }

    public function test_child_owner_sees_full_payload(): void
    {
        $owner = User::factory()->create();
        $canonicalOwner = User::factory()->create();
        $category = Category::factory()->withDepartments()->create();
        $district = District::factory()->create();

        $canonical = Issue::factory()->withStatus(IssueStatus::Open)->create([
            'user_id' => $canonicalOwner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
        ]);

        $child = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => $owner->id,
            'title' => 'My duplicate report',
            'content' => 'My duplicate details',
            'address' => 'My address',
        ]);

        IssueParticipant::factory()->duplicate($child)->create([
            'issue_id' => $canonical->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/issues/{$child->id}");

        $response->assertOk()
            ->assertJsonPath('title', 'My duplicate report')
            ->assertJsonPath('content', 'My duplicate details')
            ->assertJsonPath('address', 'My address')
            ->assertJsonPath('duplicate_of_id', $canonical->id)
            ->assertJsonPath('is_duplicate_child', true)
            ->assertJsonPath('canonical_issue_id', $canonical->id);
    }

    public function test_officer_sees_full_canonical_payload(): void
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
            'title' => 'Officer-visible title',
            'content' => 'Officer-visible content',
            'address' => 'Officer-visible address',
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$canonical->id}");

        $response->assertOk()
            ->assertJsonPath('title', 'Officer-visible title')
            ->assertJsonPath('content', 'Officer-visible content')
            ->assertJsonPath('address', 'Officer-visible address');
    }
}
