<?php

namespace Tests\Feature\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\CommunityPost;
use App\Models\District;
use App\Models\DomainNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_preference_off_hides_status_notifications(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_status_changes' => false,
            'notify_district_news' => true,
        ]);

        $status = DomainNotification::factory()->forUser($user)->statusChange()->unread()->create();
        DomainNotification::factory()->forUser($user)->newIssue()->unread()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->assertFalse($status->fresh()->is_read);
    }

    public function test_wijknieuws_preference_off_hides_community_post_notifications(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_status_changes' => true,
            'notify_district_news' => false,
        ]);
        $district = District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($district->id);

        $post = CommunityPost::factory()->create(['district_id' => $district->id]);
        DomainNotification::factory()->forUser($user)->newCommunityPost()->unread()->create([
            'community_post_id' => $post->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_empty_feed_districts_hides_community_post_notifications(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_district_news' => true,
        ]);
        $district = District::factory()->create(['is_active' => true]);
        $post = CommunityPost::factory()->create(['district_id' => $district->id]);

        DomainNotification::factory()->forUser($user)->newCommunityPost()->unread()->create([
            'community_post_id' => $post->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_community_post_notification_requires_matching_feed_district(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_district_news' => true,
        ]);
        $selected = District::factory()->create(['is_active' => true]);
        $other = District::factory()->create(['is_active' => true]);
        $user->feedDistricts()->attach($selected->id);

        $visiblePost = CommunityPost::factory()->create(['district_id' => $selected->id]);
        $hiddenPost = CommunityPost::factory()->create(['district_id' => $other->id]);

        $visible = DomainNotification::factory()->forUser($user)->newCommunityPost()->unread()->create([
            'community_post_id' => $visiblePost->id,
            'title' => 'Visible post',
        ]);
        DomainNotification::factory()->forUser($user)->newCommunityPost()->unread()->create([
            'community_post_id' => $hiddenPost->id,
            'title' => 'Hidden post',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_officer_comment_visible_when_status_preference_on(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_status_changes' => true,
        ]);

        $visible = DomainNotification::factory()->forUser($user)->newComment()->unread()->create([
            'actor_type' => ActorType::Officer,
        ]);
        DomainNotification::factory()->forUser($user)->newComment()->unread()->create([
            'actor_type' => ActorType::User,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_bulk_read_skips_notifications_hidden_by_preferences(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'notify_status_changes' => false,
            'notify_district_news' => true,
        ]);

        $hidden = DomainNotification::factory()->forUser($user)->statusChange()->unread()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/bulk-read', ['ids' => [$hidden->id]])
            ->assertOk()
            ->assertJsonPath('updated', 0);

        $this->assertFalse($hidden->fresh()->is_read);
    }
}
