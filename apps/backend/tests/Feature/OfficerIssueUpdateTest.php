<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Hub;
use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\OfficerIssueUpdate;
use App\Models\OfficerIssueUpdateAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficerIssueUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private function tokenFor($actor): string
    {
        return $actor->createToken('test-token')->plainTextToken;
    }

    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenFor($actor)];
    }

    private function setupOfficer(Hub $hub, District $district, bool $hasActiveShift = true): Officer
    {
        $officer = Officer::create([
            'username' => 'test-officer-' . fake()->uuid(),
            'email' => 'officer-' . fake()->uuid() . '@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-' . fake()->uuid(),
            'hub_id' => $hub->id,
            'is_active' => true,
        ]);

        if ($hasActiveShift) {
            $officer->forceFill(['hub_active_until' => now()->addHours(10)])->save();
        }

        $officer->districts()->attach($district->id);

        return $officer;
    }

    public function test_index_returns_paginated_officer_updates(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        OfficerIssueUpdate::factory()->count(3)->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->getJson("/api/issues/{$issue->id}/officer-updates");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_citizen_cannot_post_officer_update(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'citizen update',
                'content' => 'citizen content',
            ]);

        $response->assertForbidden();
    }

    public function test_officer_without_active_shift_cannot_post_update(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district, false); // no active shift
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'shiftless update',
                'content' => 'content',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'hub_active_required');
    }

    public function test_officer_outside_district_cannot_post_update(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district1 = District::factory()->create(['hub_id' => $hub->id]);
        $district2 = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district1); // in district 1 only
        $issue = Issue::factory()->create([
            'district_id' => $district2->id, // issue is in district 2
            'assigned_officer_id' => $officer->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'out of bounds update',
                'content' => 'content',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'officer_not_in_district');
    }

    public function test_unassigned_officer_cannot_post_update(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer1 = $this->setupOfficer($hub, $district);
        $officer2 = $this->setupOfficer($hub, $district); // both assigned to district
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer1->id, // assigned to officer 1
        ]);

        $response = $this->withHeaders($this->authHeaders($officer2))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'not my issue update',
                'content' => 'content',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'not_assigned_officer');
    }

    public function test_assigned_officer_can_post_update_with_optional_attachments(): void
    {
        Storage::fake('local');

        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $file2 = UploadedFile::fake()->image('photo2.png');

        $response = $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'First Update',
                'content' => 'Work started.',
                'files' => [$file1, $file2],
            ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'First Update')
            ->assertJsonPath('content', 'Work started.')
            ->assertJsonCount(2, 'attachments');

        $update = OfficerIssueUpdate::firstOrFail();
        $this->assertSame($officer->id, $update->officer_id);
        $this->assertCount(2, $update->attachments);

        // Verify storage
        foreach ($update->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->file_path);
        }
    }

    public function test_creation_enforces_max_attachments_cap(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $files = [
            UploadedFile::fake()->image('1.jpg'),
            UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'),
            UploadedFile::fake()->image('4.jpg'),
        ];

        $response = $this->withHeaders($this->authHeaders($officer))
            ->postJson("/api/issues/{$issue->id}/officer-updates", [
                'title' => 'Too many files',
                'content' => 'content',
                'files' => $files,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }

    public function test_author_officer_can_update_their_update_and_manage_attachments(): void
    {
        Storage::fake('local');

        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $update = OfficerIssueUpdate::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
            'title' => 'Old Title',
            'content' => 'Old content',
        ]);

        $attachment = OfficerIssueUpdateAttachment::factory()->create([
            'officer_issue_update_id' => $update->id,
            'file_path' => 'officer-issue-update-attachments/' . $update->id . '/old.jpg',
        ]);

        Storage::disk('local')->put($attachment->file_path, 'fake content');

        $newFile = UploadedFile::fake()->image('new.png');

        $response = $this->withHeaders($this->authHeaders($officer))
            ->patchJson("/api/issues/{$issue->id}/officer-updates/{$update->id}", [
                'title' => 'New Title',
                'content' => 'New content',
                'remove_attachment_ids' => [$attachment->id],
                'files' => [$newFile],
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'New Title')
            ->assertJsonPath('content', 'New content')
            ->assertJsonCount(1, 'attachments');

        // Verify attachment removed
        $this->assertDatabaseMissing('officer_issue_update_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->file_path);

        // Verify new attachment added
        $update->refresh();
        $this->assertCount(1, $update->attachments);
        $this->assertNotEquals($attachment->id, $update->attachments->first()->id);
        Storage::disk('local')->assertExists($update->attachments->first()->file_path);
    }

    public function test_other_officer_cannot_update_update(): void
    {
        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer1 = $this->setupOfficer($hub, $district);
        $officer2 = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer1->id,
        ]);

        $update = OfficerIssueUpdate::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer1->id,
        ]);

        // Officer 2 attempts to edit Officer 1's update on Officer 1's assigned issue
        $response = $this->withHeaders($this->authHeaders($officer2))
            ->patchJson("/api/issues/{$issue->id}/officer-updates/{$update->id}", [
                'title' => 'Edit',
                'content' => 'Content',
            ]);

        $response->assertForbidden();
    }

    public function test_author_officer_can_delete_update(): void
    {
        Storage::fake('local');

        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
        ]);

        $update = OfficerIssueUpdate::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
        ]);

        $attachment = OfficerIssueUpdateAttachment::factory()->create([
            'officer_issue_update_id' => $update->id,
            'file_path' => 'officer-issue-update-attachments/' . $update->id . '/delete-me.jpg',
        ]);

        Storage::disk('local')->put($attachment->file_path, 'fake content');

        $response = $this->withHeaders($this->authHeaders($officer))
            ->deleteJson("/api/issues/{$issue->id}/officer-updates/{$update->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('officer_issue_updates', ['id' => $update->id]);
        $this->assertDatabaseMissing('officer_issue_update_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->file_path);
    }

    public function test_download_attachment_visibility(): void
    {
        Storage::fake('local');

        $hub = Hub::factory()->create(['is_active' => true]);
        $district = District::factory()->create(['hub_id' => $hub->id]);
        $officer = $this->setupOfficer($hub, $district);
        $issue = Issue::factory()->create([
            'district_id' => $district->id,
            'assigned_officer_id' => $officer->id,
            'visibility' => 'visible',
        ]);

        $update = OfficerIssueUpdate::factory()->create([
            'issue_id' => $issue->id,
            'officer_id' => $officer->id,
        ]);

        $attachment = OfficerIssueUpdateAttachment::factory()->create([
            'officer_issue_update_id' => $update->id,
            'file_path' => 'officer-issue-update-attachments/' . $update->id . '/test.jpg',
            'original_name' => 'test.jpg',
        ]);

        Storage::disk('local')->put($attachment->file_path, 'file-content');

        // Regular citizen who can view the issue can download the attachment
        $citizen = User::factory()->create();

        $this->withHeaders($this->authHeaders($citizen))
            ->getJson("/api/issues/{$issue->id}/officer-updates/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=test.jpg');

        // Deactivated user or user who cannot view the issue gets 404/403
        $otherIssue = Issue::factory()->create(['visibility' => 'hidden']); // another hidden issue
        $citizen2 = User::factory()->create();

        // Testing wrong issue mapping
        $this->withHeaders($this->authHeaders($citizen2))
            ->getJson("/api/issues/{$otherIssue->id}/officer-updates/attachments/{$attachment->id}/download")
            ->assertNotFound();
    }
}
