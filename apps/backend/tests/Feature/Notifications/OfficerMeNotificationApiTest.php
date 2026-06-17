<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerMeNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_lists_only_own_notifications(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $otherOfficer = Officer::factory()->create(['is_active' => true]);

        $own = DomainNotification::factory()->forOfficer($officer)->create();
        DomainNotification::factory()->forOfficer($otherOfficer)->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_officer_unread_count_returns_only_unread_rows(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forOfficer($officer)->unread()->count(2)->create();
        DomainNotification::factory()->forOfficer($officer)->read()->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 2);
    }

    public function test_officer_mark_single_notification_read_is_idempotent(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $notification = DomainNotification::factory()->forOfficer($officer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/officers/me/notifications/{$notification->id}", ['is_read' => true])
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/officers/me/notifications/{$notification->id}", ['is_read' => true])
            ->assertOk()
            ->assertJsonPath('data.is_read', true);
    }

    public function test_officer_update_returns_404_for_foreign_notification(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $otherOfficer = Officer::factory()->create(['is_active' => true]);
        $foreign = DomainNotification::factory()->forOfficer($otherOfficer)->create();

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/officers/me/notifications/{$foreign->id}", ['is_read' => true])
            ->assertNotFound();
    }

    public function test_officer_bulk_read_skips_foreign_ids_silently(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $otherOfficer = Officer::factory()->create(['is_active' => true]);

        $own = DomainNotification::factory()->forOfficer($officer)->unread()->create();
        $foreign = DomainNotification::factory()->forOfficer($otherOfficer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->patchJson('/api/officers/me/notifications/bulk-read', ['ids' => [$own->id, $foreign->id]])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertTrue($own->fresh()->is_read);
        $this->assertFalse($foreign->fresh()->is_read);
    }

    public function test_officer_since_filter_excludes_older_notifications(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forOfficer($officer)->create([
            'created_at' => now()->subDays(2),
        ]);
        $recent = DomainNotification::factory()->forOfficer($officer)->create([
            'created_at' => now()->subHour(),
        ]);

        $since = now()->subDay()->toIso8601String();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications?since='.urlencode($since))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id);
    }

    public function test_officer_type_filter_limits_results(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forOfficer($officer)->newIssue()->create();
        $feedback = DomainNotification::factory()->forOfficer($officer)->feedbackReceived()->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications?type='.NotificationType::FeedbackReceived->value)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $feedback->id);
    }

    public function test_officer_is_read_filter_limits_results(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        $unread = DomainNotification::factory()->forOfficer($officer)->unread()->create();
        DomainNotification::factory()->forOfficer($officer)->read()->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications?is_read=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unread->id);
    }

    public function test_officer_list_pagination_respects_per_page(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forOfficer($officer)->count(3)->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications?per_page=2&page=1')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_officer_mark_all_read_updates_only_own_unread_notifications(): void
    {
        $officer = Officer::factory()->create(['is_active' => true]);
        $otherOfficer = Officer::factory()->create(['is_active' => true]);

        DomainNotification::factory()->forOfficer($officer)->unread()->count(2)->create();
        $foreignUnread = DomainNotification::factory()->forOfficer($otherOfficer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/officers/me/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('updated', 2);

        $this->assertSame(0, DomainNotification::query()->forOfficer($officer)->unread()->count());
        $this->assertFalse($foreignUnread->fresh()->is_read);
    }

    public function test_officer_tier_b_endpoints_succeed_without_hub_active_session(): void
    {
        $officer = Officer::factory()->create([
            'is_active' => true,
            'hub_active_until' => null,
        ]);

        $notification = DomainNotification::factory()->forOfficer($officer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/officers/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/officers/me/notifications/{$notification->id}", ['is_read' => true])
            ->assertOk();

        $second = DomainNotification::factory()->forOfficer($officer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->patchJson('/api/officers/me/notifications/bulk-read', ['ids' => [$second->id]])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        DomainNotification::factory()->forOfficer($officer)->unread()->create();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/officers/me/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('updated', 1);
    }

    public function test_user_receives_403_on_officer_me_notification_routes(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/officers/me/notifications/unread-count')
            ->assertForbidden();
    }
}
