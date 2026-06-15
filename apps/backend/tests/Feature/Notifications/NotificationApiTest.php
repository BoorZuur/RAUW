<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_lists_only_own_notifications(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $own = DomainNotification::factory()->forUser($user)->create();
        DomainNotification::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_officer_receives_403_on_user_notification_routes(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/notifications')
            ->assertForbidden();
    }

    public function test_user_receives_403_on_officer_notification_routes(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/officers/me/notifications')
            ->assertForbidden();
    }

    public function test_unread_count_returns_only_unread_rows(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forUser($user)->unread()->count(2)->create();
        DomainNotification::factory()->forUser($user)->read()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 2);
    }

    public function test_mark_single_notification_read_is_idempotent(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $notification = DomainNotification::factory()->forUser($user)->unread()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}", ['is_read' => true])
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}", ['is_read' => true])
            ->assertOk()
            ->assertJsonPath('data.is_read', true);
    }

    public function test_update_returns_404_for_foreign_notification(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $foreign = DomainNotification::factory()->forUser($otherUser)->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$foreign->id}", ['is_read' => true])
            ->assertNotFound();
    }

    public function test_bulk_read_skips_foreign_ids_silently(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $own = DomainNotification::factory()->forUser($user)->unread()->create();
        $foreign = DomainNotification::factory()->forUser($otherUser)->unread()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/bulk-read', ['ids' => [$own->id, $foreign->id]])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertTrue($own->fresh()->is_read);
        $this->assertFalse($foreign->fresh()->is_read);
    }

    public function test_officer_can_list_notifications_without_hub_active_session(): void
    {
        $officer = Officer::factory()->create([
            'is_active' => true,
            'hub_active_until' => null,
        ]);

        DomainNotification::factory()->forOfficer($officer)->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_since_filter_excludes_older_notifications(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forUser($user)->create([
            'created_at' => now()->subDays(2),
        ]);
        $recent = DomainNotification::factory()->forUser($user)->create([
            'created_at' => now()->subHour(),
        ]);

        $since = now()->subDay()->toIso8601String();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?since='.urlencode($since))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id);
    }

    public function test_type_filter_limits_results(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forUser($user)->statusChange()->create();
        $message = DomainNotification::factory()->forUser($user)->newMessage()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?type='.NotificationType::NewMessage->value)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $message->id);
    }
}
