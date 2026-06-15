<?php

namespace Tests\Feature\CommunityPosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommunityPostSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_save_visible_post_in_feed_district(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/community-posts/{$post->id}/save");

        $response->assertStatus(201);
        $this->assertDatabaseHas('community_post_user', ['user_id' => $user->id, 'community_post_id' => $post->id]);
    }

    public function test_user_cannot_save_hidden_post(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'hidden',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/community-posts/{$post->id}/save");

        $response->assertStatus(404);
        $this->assertDatabaseMissing('community_post_user', ['user_id' => $user->id, 'community_post_id' => $post->id]);
    }

    public function test_user_cannot_save_post_outside_feed_district(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $otherDistrict = \App\Models\District::factory()->create(['is_active' => true]);

        $post = \App\Models\CommunityPost::factory()->create([
            'district_id' => $otherDistrict->id,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/community-posts/{$post->id}/save");

        $response->assertStatus(404);
        $this->assertDatabaseMissing('community_post_user', ['user_id' => $user->id, 'community_post_id' => $post->id]);
    }

    public function test_saved_index_returns_only_saved_posts(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $post1 = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);
        
        $post2 = \App\Models\CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $user->savedCommunityPosts()->attach($post1->id);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/community-posts?district_id={$district->id}&saved_only=1");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post1->id);
    }
}
