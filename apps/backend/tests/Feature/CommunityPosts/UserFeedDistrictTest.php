<?php

namespace Tests\Feature\CommunityPosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserFeedDistrictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_sync_active_feed_districts(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district1 = \App\Models\District::factory()->create(['is_active' => true]);
        $district2 = \App\Models\District::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/auth/me/feed-districts', [
            'district_ids' => [$district1->id, $district2->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'profile.districts');

        $this->assertDatabaseHas('district_user', ['user_id' => $user->id, 'district_id' => $district1->id]);
        $this->assertDatabaseHas('district_user', ['user_id' => $user->id, 'district_id' => $district2->id]);
    }

    public function test_user_cannot_sync_inactive_district(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/auth/me/feed-districts', [
            'district_ids' => [$district->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('district_ids.0');
    }

    public function test_profile_includes_districts(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('profile.districts.0.id', $district->id);
    }

    public function test_district_deactivate_prunes_pivot_and_nulls_posts(): void
    {
        $manager = \App\Models\Manager::factory()->create(['is_active' => true, 'is_main_manager' => true]);
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $district = \App\Models\District::factory()->create(['is_active' => true]);
        
        $user->feedDistricts()->attach($district->id);
        
        $post = \App\Models\CommunityPost::factory()->create(['district_id' => $district->id]);

        $response = $this->actingAs($manager, 'sanctum')->patchJson("/api/districts/{$district->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('district_user', ['user_id' => $user->id, 'district_id' => $district->id]);
        $this->assertDatabaseHas('community_posts', ['id' => $post->id, 'district_id' => null]);
    }
}
