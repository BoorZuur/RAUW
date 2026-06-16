<?php

namespace Tests\Unit;

use App\Enums\JoinedVia;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\Issues\IssueAnonymousDisplayName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueAnonymousDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_anonymous_returns_username()
    {
        $user = User::factory()->create(['username' => 'johndoe']);
        $issue = Issue::factory()->create(['user_id' => $user->id, 'is_anonymous' => false]);
        
        $result = IssueAnonymousDisplayName::derive($user, $issue);
        $this->assertEquals(['is_anonymous' => false, 'id' => $user->id, 'username' => 'johndoe'], $result);
    }

    public function test_anonymous_creator_participant_returns_issue_alias()
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create([
            'user_id' => $user->id, 
            'is_anonymous' => true, 
            'anonymous_alias' => 'Melder#ABCD'
        ]);

        IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'is_anonymous' => true,
            'joined_via' => JoinedVia::Creator,
            'joined_at' => now(),
        ]);

        $result = IssueAnonymousDisplayName::derive($user, $issue);
        $this->assertEquals(['is_anonymous' => true, 'display_name' => 'Melder#ABCD'], $result);
    }

    public function test_anonymous_manual_participant_returns_deelnemer_alias()
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create(['is_anonymous' => true]);

        $participant = IssueParticipant::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'is_anonymous' => true,
            'joined_via' => JoinedVia::Manual,
            'joined_at' => now(),
        ]);

        $result = IssueAnonymousDisplayName::derive($user, $issue);
        $this->assertEquals(['is_anonymous' => true, 'display_name' => 'Deelnemer#' . str_pad((string) $participant->id, 6, '0', STR_PAD_LEFT)], $result);
    }
}
