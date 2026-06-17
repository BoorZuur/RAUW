<?php

namespace Tests\Feature\User;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_default_notification_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/settings')
            ->assertOk()
            ->assertJsonPath('data.notify_status_changes', true)
            ->assertJsonPath('data.notify_district_news', true);
    }

    public function test_user_can_patch_notification_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'notify_status_changes' => false,
                'notify_district_news' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.notify_status_changes', false)
            ->assertJsonPath('data.notify_district_news', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'notify_status_changes' => false,
            'notify_district_news' => true,
        ]);
    }

    public function test_user_can_partially_patch_notification_settings(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_status_changes' => true,
            'notify_district_news' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'notify_district_news' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.notify_status_changes', true)
            ->assertJsonPath('data.notify_district_news', false);
    }

    public function test_officer_cannot_access_user_settings(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/user/settings')
            ->assertForbidden();
    }

    public function test_patch_rejects_invalid_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'notify_status_changes' => 'yes',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notify_status_changes');
    }
}
