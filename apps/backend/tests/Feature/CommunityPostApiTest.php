<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\District;
use App\Models\Officer;
use App\Models\User;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\DepartmentsTableSeeder;

class CommunityPostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed categories since standard factories require them
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_officer_can_create_community_post_in_assigned_district(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $district = District::factory()->create();
        
        $officer->districts()->attach($district->id);

        // Required middleware in api routes for active officers
        $response = $this->actingAs($officer, 'sanctum')
            ->withSession(['hub_active' => true]) // Bypass tier A middleware since it's just api testing without full auth setup
            ->postJson('/api/community-posts', [
                'district_id' => $district->id,
                'title' => 'Test Title',
                'content' => 'Test body for the community post.',
                'visibility' => 'visible',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Title')
            ->assertJsonPath('data.district.id', $district->id);

        $this->assertDatabaseHas('community_posts', [
            'officer_id' => $officer->id,
            'district_id' => $district->id,
            'title' => 'Test Title',
        ]);
    }

    public function test_user_can_view_post_in_feed_district(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $district = District::factory()->create();
        
        $user->feedDistricts()->attach($district->id);

        $post = CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/community-posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.is_saved', false);
    }

    public function test_user_can_save_and_unsave_community_post(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $district = District::factory()->create();
        
        $user->feedDistricts()->attach($district->id);

        $post = CommunityPost::factory()->create([
            'district_id' => $district->id,
            'visibility' => 'visible',
        ]);

        // Save
        $this->actingAs($user, 'sanctum')->postJson("/api/community-posts/{$post->id}/save")
            ->assertStatus(201);

        $this->assertDatabaseHas('community_post_user', [
            'user_id' => $user->id,
            'community_post_id' => $post->id,
        ]);

        // Check index filtering by saved (use 1 for true to pass boolean validation cleanly in URL)
        $response = $this->getJson("/api/community-posts?saved_only=1");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.is_saved', true);

        // Unsave
        $this->deleteJson("/api/community-posts/{$post->id}/save")
            ->assertStatus(204);

        $this->assertDatabaseMissing('community_post_user', [
            'user_id' => $user->id,
            'community_post_id' => $post->id,
        ]);
    }
}
