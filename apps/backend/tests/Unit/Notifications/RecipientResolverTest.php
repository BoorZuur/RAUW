<?php

namespace Tests\Unit\Notifications;

use App\Enums\ActorType;
use App\Models\CommunityPost;
use App\Models\Department;
use App\Models\District;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueOfficerAssignmentHistory;
use App\Models\IssueParticipant;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Models\User;
use App\Support\Notifications\ResolveCanonicalIssueParticipants;
use App\Support\Notifications\ResolveDistrictFeedFollowers;
use App\Support\Notifications\ResolveDistrictOfficersForIssue;
use App\Support\Notifications\ResolveFeedbackRecipientOfficer;
use App\Support\Notifications\ResolveIssueChatPartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_issue_participants_include_owner_and_participant_rows(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $canonical = Issue::factory()->create(['user_id' => $owner->id]);

        IssueParticipant::factory()->create([
            'issue_id' => $canonical->id,
            'user_id' => $participant->id,
        ]);

        $resolver = new ResolveCanonicalIssueParticipants;
        $userIds = $resolver->userIds($canonical);

        $this->assertEqualsCanonicalizing([$owner->id, $participant->id], $userIds->all());
    }

    public function test_duplicate_issue_resolves_participants_on_root_issue_only(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $canonical = Issue::factory()->create(['user_id' => $owner->id]);

        IssueParticipant::factory()->create([
            'issue_id' => $canonical->id,
            'user_id' => $participant->id,
        ]);

        $duplicate = Issue::factory()->asDuplicateOf($canonical)->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $resolver = new ResolveCanonicalIssueParticipants;
        $userIds = $resolver->userIds($duplicate);

        $this->assertEqualsCanonicalizing([$owner->id, $participant->id], $userIds->all());
        $this->assertNotContains($duplicate->user_id, $userIds->all());
    }

    public function test_assigned_officer_id_reads_from_canonical_issue(): void
    {
        $officer = Officer::factory()->create();
        $canonical = Issue::factory()->create(['assigned_officer_id' => $officer->id]);
        $duplicate = Issue::factory()->asDuplicateOf($canonical)->create([
            'assigned_officer_id' => null,
        ]);

        $resolver = new ResolveCanonicalIssueParticipants;

        $this->assertSame($officer->id, $resolver->assignedOfficerId($canonical));
        $this->assertSame($officer->id, $resolver->assignedOfficerId($duplicate));
    }

    public function test_issue_chat_partner_officer_sender_targets_chat_user(): void
    {
        $officer = Officer::factory()->create();
        $user = User::factory()->create();
        $canonical = Issue::factory()->create(['assigned_officer_id' => $officer->id]);
        $chat = IssueChat::factory()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $target = (new ResolveIssueChatPartner)->resolve($chat, $canonical, $officer);

        $this->assertNotNull($target);
        $this->assertSame(ActorType::User, $target->recipientType);
        $this->assertSame($user->id, $target->userId);
        $this->assertSame($canonical->id, $target->issueId);
    }

    public function test_issue_chat_partner_user_sender_targets_assigned_officer(): void
    {
        $officer = Officer::factory()->create();
        $user = User::factory()->create();
        $canonical = Issue::factory()->create(['assigned_officer_id' => $officer->id]);
        $chat = IssueChat::factory()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $target = (new ResolveIssueChatPartner)->resolve($chat, $canonical, $user);

        $this->assertNotNull($target);
        $this->assertSame(ActorType::Officer, $target->recipientType);
        $this->assertSame($officer->id, $target->officerId);
        $this->assertSame($canonical->id, $target->issueId);
    }

    public function test_issue_chat_partner_returns_null_when_user_sends_without_assigned_officer(): void
    {
        $user = User::factory()->create();
        $canonical = Issue::factory()->create(['assigned_officer_id' => null]);
        $chat = IssueChat::factory()->create([
            'issue_id' => $canonical->id,
            'user_id' => $user->id,
        ]);

        $target = (new ResolveIssueChatPartner)->resolve($chat, $canonical, $user);

        $this->assertNull($target);
    }

    public function test_district_officers_intersects_department_assignments(): void
    {
        $district = District::factory()->create();
        $issueDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $issue = Issue::factory()->create(['district_id' => $district->id]);
        $issue->syncDepartments([$issueDepartment->id]);

        $matchingOfficer = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$issueDepartment])
            ->create();

        Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$otherDepartment])
            ->create();

        $officerIds = (new ResolveDistrictOfficersForIssue)->officerIds($issue);

        $this->assertSame([$matchingOfficer->id], $officerIds->all());
    }

    public function test_district_officers_falls_back_to_all_district_officers_when_intersection_empty(): void
    {
        $district = District::factory()->create();
        $issueDepartment = Department::factory()->create();
        $officerDepartment = Department::factory()->create();

        $issue = Issue::factory()->create(['district_id' => $district->id]);
        $issue->syncDepartments([$issueDepartment->id]);

        $officerOne = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$officerDepartment])
            ->create();

        $officerTwo = Officer::factory()
            ->withDistricts([$district])
            ->withDepartments([$officerDepartment])
            ->create();

        $officerIds = (new ResolveDistrictOfficersForIssue)->officerIds($issue);

        $this->assertEqualsCanonicalizing([$officerOne->id, $officerTwo->id], $officerIds->all());
    }

    public function test_district_feed_followers_returns_users_following_post_district(): void
    {
        $district = District::factory()->create();
        $otherDistrict = District::factory()->create();
        $follower = User::factory()->create();
        $otherFollower = User::factory()->create();

        $follower->feedDistricts()->attach($district->id);
        $otherFollower->feedDistricts()->attach($otherDistrict->id);

        $post = CommunityPost::factory()->create(['district_id' => $district->id]);

        $userIds = (new ResolveDistrictFeedFollowers)->userIds($post);

        $this->assertSame([$follower->id], $userIds->all());
    }

    public function test_feedback_recipient_prefers_assigned_officer(): void
    {
        $assignedOfficer = Officer::factory()->create();
        $resolutionOfficer = Officer::factory()->create();
        $historyOfficer = Officer::factory()->create();

        $issue = Issue::factory()->create(['assigned_officer_id' => $assignedOfficer->id]);

        OfficerIssueResolution::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $resolutionOfficer->id,
        ]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $historyOfficer->id,
            'assigned_at' => now()->subDay(),
        ]);

        $this->assertSame(
            $assignedOfficer->id,
            (new ResolveFeedbackRecipientOfficer)->officerId($issue),
        );
    }

    public function test_feedback_recipient_falls_back_to_resolution_officer(): void
    {
        $resolutionOfficer = Officer::factory()->create();
        $historyOfficer = Officer::factory()->create();

        $issue = Issue::factory()->create(['assigned_officer_id' => null]);

        OfficerIssueResolution::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $resolutionOfficer->id,
        ]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $historyOfficer->id,
            'assigned_at' => now()->subDay(),
        ]);

        $this->assertSame(
            $resolutionOfficer->id,
            (new ResolveFeedbackRecipientOfficer)->officerId($issue),
        );
    }

    public function test_feedback_recipient_falls_back_to_latest_assignment_history(): void
    {
        $olderOfficer = Officer::factory()->create();
        $latestOfficer = Officer::factory()->create();

        $issue = Issue::factory()->create(['assigned_officer_id' => null]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $olderOfficer->id,
            'assigned_at' => now()->subDays(2),
        ]);

        IssueOfficerAssignmentHistory::query()->create([
            'issue_id' => $issue->id,
            'officer_id' => $latestOfficer->id,
            'assigned_at' => now()->subDay(),
        ]);

        $this->assertSame(
            $latestOfficer->id,
            (new ResolveFeedbackRecipientOfficer)->officerId($issue),
        );
    }

    public function test_feedback_recipient_returns_null_when_no_officer_chain_match(): void
    {
        $issue = Issue::factory()->create(['assigned_officer_id' => null]);

        $this->assertNull((new ResolveFeedbackRecipientOfficer)->officerId($issue));
    }
}
