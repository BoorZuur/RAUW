<?php

namespace Tests\Feature\CommunityPosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommunityPostManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_officer_cannot_create_post_in_unassigned_district(): void
    {
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => now()->addHours(8)]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Test',
                'content' => 'Test body',
                'visibility' => 'visible',
            ]);

        $response->assertStatus(403);
    }

    public function test_officer_cannot_create_post_in_inactive_district(): void
    {
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => now()->addHours(8)]);
        $district = \App\Models\District::factory()->create(['is_active' => false]);
        $officer->districts()->attach($district->id);

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Test',
                'content' => 'Test body',
                'visibility' => 'visible',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('district_id');
    }

    public function test_manager_feed_is_app_wide(): void
    {
        $manager = \App\Models\Manager::factory()->create(['is_active' => true]);
        $post1 = \App\Models\CommunityPost::factory()->create();
        $post2 = \App\Models\CommunityPost::factory()->create();

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/community-posts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_main_manager_can_hide_any_post(): void
    {
        $manager = \App\Models\Manager::factory()->create(['is_active' => true, 'is_main_manager' => true]);
        $post = \App\Models\CommunityPost::factory()->create(['visibility' => 'visible']);

        $response = $this->actingAs($manager, 'sanctum')->patchJson("/api/community-posts/{$post->id}/visibility", [
            'visibility' => 'hidden',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.visibility', 'hidden');

        $this->assertDatabaseHas('community_posts', ['id' => $post->id, 'visibility' => 'hidden']);
    }

    public function test_ordinary_manager_can_only_hide_posts_in_their_districts(): void
    {
        $manager = \App\Models\Manager::factory()->create(['is_active' => true, 'is_main_manager' => false]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $manager->districts()->attach($district->id);

        $post1 = \App\Models\CommunityPost::factory()->create(['district_id' => $district->id, 'visibility' => 'visible']);
        
        $otherDistrict = \App\Models\District::factory()->create(['is_active' => true]);
        $post2 = \App\Models\CommunityPost::factory()->create(['district_id' => $otherDistrict->id, 'visibility' => 'visible']);

        // Allowed
        $this->actingAs($manager, 'sanctum')->patchJson("/api/community-posts/{$post1->id}/visibility", [
            'visibility' => 'hidden',
        ])->assertStatus(200);

        // Forbidden
        $this->patchJson("/api/community-posts/{$post2->id}/visibility", [
            'visibility' => 'hidden',
        ])->assertStatus(403);
    }

    public function test_hiding_post_removes_saves(): void
    {
        $manager = \App\Models\Manager::factory()->create(['is_active' => true, 'is_main_manager' => true]);
        $post = \App\Models\CommunityPost::factory()->create(['visibility' => 'visible']);
        $user = \App\Models\User::factory()->create();
        
        $user->savedCommunityPosts()->attach($post->id);
        $this->assertDatabaseHas('community_post_user', ['user_id' => $user->id, 'community_post_id' => $post->id]);

        $this->actingAs($manager, 'sanctum')->patchJson("/api/community-posts/{$post->id}/visibility", [
            'visibility' => 'hidden',
        ])->assertStatus(200);

        $this->assertDatabaseMissing('community_post_user', ['user_id' => $user->id, 'community_post_id' => $post->id]);
    }

    public function test_officer_without_active_shift_can_browse_community_posts(): void
    {
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => null]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $officer->districts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/community-posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/community-posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_officer_without_active_shift_cannot_create_community_post(): void
    {
        $officer = \App\Models\Officer::factory()->create(['is_active' => true, 'hub_active_until' => null]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $officer->districts()->attach($district->id);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Test',
                'content' => 'Test body',
                'visibility' => 'visible',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'hub_active_required');
    }
}
