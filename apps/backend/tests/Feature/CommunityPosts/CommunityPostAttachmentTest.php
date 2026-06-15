<?php

namespace Tests\Feature\CommunityPosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommunityPostAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_officer_can_upload_attachments_up_to_limit(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => now()->addHours(8)]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $officer->districts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'officer_id' => $officer->id,
            'district_id' => $district->id,
        ]);

        $file1 = \Illuminate\Http\Testing\File::create('test1.jpg', 100);
        $file2 = \Illuminate\Http\Testing\File::create('test2.jpg', 100);

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/community-posts/{$post->id}/attachments", [
                'attachments' => [$file1, $file2],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(2);

        $this->assertDatabaseCount('community_post_attachments', 2);
    }

    public function test_attachment_limit_enforced(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => now()->addHours(8)]);
        $post = \App\Models\CommunityPost::factory()->create(['officer_id' => $officer->id]);

        \App\Models\CommunityPostAttachment::factory()->count(4)->create([
            'community_post_id' => $post->id,
        ]);

        $file1 = \Illuminate\Http\Testing\File::create('test1.jpg', 100);
        $file2 = \Illuminate\Http\Testing\File::create('test2.jpg', 100);

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson("/api/community-posts/{$post->id}/attachments", [
                'attachments' => [$file1, $file2],
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_download_visible_attachment(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $file = \Illuminate\Http\Testing\File::create('test.jpg', 100);
        $path = $file->store('community-post-attachments', 'local');

        $attachment = \App\Models\CommunityPostAttachment::factory()->create([
            'community_post_id' => $post->id,
            'file_path' => $path,
            'file_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/community-posts/{$post->id}/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
    }

    public function test_officer_without_active_shift_can_download_attachment(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => null]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $officer->districts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $file = \Illuminate\Http\Testing\File::create('test.jpg', 100);
        $path = $file->store('community-post-attachments', 'local');

        $attachment = \App\Models\CommunityPostAttachment::factory()->create([
            'community_post_id' => $post->id,
            'file_path' => $path,
            'file_type' => 'image/jpeg',
        ]);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/community-posts/{$post->id}/attachments/{$attachment->id}/download")
            ->assertOk();
    }
}
