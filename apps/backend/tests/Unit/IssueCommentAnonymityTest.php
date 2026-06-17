<?php

namespace Tests\Unit;

use App\Enums\ActorType;
use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\Issues\IssueCommentAnonymity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCommentAnonymityTest extends TestCase
{
    use RefreshDatabase;

    private IssueCommentAnonymity $anonymity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->anonymity = new IssueCommentAnonymity;
    }

    public function test_default_for_owner_on_anonymous_issue(): void
    {
        $owner = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
            'anonymous_alias' => 'Melder#TEST',
        ]);

        $this->assertTrue($this->anonymity->defaultForUserOnIssue($owner, $issue));
    }

    public function test_default_for_anonymous_participant(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'is_anonymous' => true,
            'joined_via' => JoinedVia::Manual,
            'joined_at' => now(),
        ]);

        $this->assertTrue($this->anonymity->defaultForUserOnIssue($participant, $issue));
    }

    public function test_default_false_for_non_participant_on_public_issue(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => false,
        ]);

        $this->assertFalse($this->anonymity->defaultForUserOnIssue($outsider, $issue));
    }

    public function test_default_false_for_identified_participant_on_anonymous_issue(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'is_anonymous' => false,
            'joined_via' => JoinedVia::Manual,
            'joined_at' => now(),
        ]);

        $this->assertFalse($this->anonymity->defaultForUserOnIssue($participant, $issue));
    }

    public function test_should_redact_user_anonymous_comment(): void
    {
        $comment = IssueComment::factory()->anonymous()->create([
            'author_type' => ActorType::User,
        ]);

        $this->assertTrue($this->anonymity->shouldRedact($comment));
    }

    public function test_should_not_redact_identified_user_comment(): void
    {
        $comment = IssueComment::factory()->create([
            'author_type' => ActorType::User,
            'is_anonymous' => false,
        ]);

        $this->assertFalse($this->anonymity->shouldRedact($comment));
    }

    public function test_hash_alias_is_stable_for_same_inputs(): void
    {
        $first = $this->anonymity->hashAlias(42, 99);
        $second = $this->anonymity->hashAlias(42, 99);

        $this->assertSame($first, $second);
        $this->assertStringStartsWith('Deelnemer#', $first);
        $this->assertMatchesRegularExpression('/^Deelnemer#\d{6}$/', $first);
    }

    public function test_hash_alias_differs_for_different_users(): void
    {
        $aliasA = $this->anonymity->hashAlias(1, 100);
        $aliasB = $this->anonymity->hashAlias(2, 100);

        $this->assertNotSame($aliasA, $aliasB);
    }

    public function test_resolve_display_name_owner_alias_on_anonymous_issue(): void
    {
        $owner = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
            'anonymous_alias' => 'Melder#XY99',
        ]);

        $comment = IssueComment::factory()->anonymous()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);
        $comment->setRelation('user', $owner);
        $comment->setRelation('issue', $issue);

        $resolved = $this->anonymity->resolveDisplayName($comment);

        $this->assertSame([
            'is_anonymous' => true,
            'display_name' => 'Melder#XY99',
        ], $resolved);
    }

    public function test_resolve_display_name_hash_for_non_participant(): void
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => false,
        ]);

        $comment = IssueComment::factory()->anonymous()->create([
            'issue_id' => $issue->id,
            'user_id' => $commenter->id,
        ]);
        $comment->setRelation('user', $commenter);
        $comment->setRelation('issue', $issue);

        $expected = $this->anonymity->hashAlias($commenter->id, $issue->id);

        $this->assertSame([
            'is_anonymous' => true,
            'display_name' => $expected,
        ], $this->anonymity->resolveDisplayName($comment));
    }

    public function test_resolve_display_name_participant_alias(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
        ]);

        $participantRow = IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
            'is_anonymous' => true,
            'joined_via' => JoinedVia::Manual,
            'joined_at' => now(),
        ]);

        $comment = IssueComment::factory()->anonymous()->create([
            'issue_id' => $issue->id,
            'user_id' => $participant->id,
        ]);
        $comment->setRelation('user', $participant);
        $comment->setRelation('issue', $issue);

        $expectedAlias = 'Deelnemer#'.str_pad((string) $participantRow->id, 6, '0', STR_PAD_LEFT);

        $this->assertSame([
            'is_anonymous' => true,
            'display_name' => $expectedAlias,
        ], $this->anonymity->resolveDisplayName($comment));
    }
}
