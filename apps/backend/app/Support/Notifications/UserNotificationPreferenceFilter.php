<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserNotificationPreferenceFilter
{
    /**
     * @var list<NotificationType>
     */
    private const STATUS_TYPES = [
        NotificationType::StatusChange,
        NotificationType::NewMessage,
        NotificationType::ChatOpened,
        NotificationType::ChatClosed,
        NotificationType::ResolutionPosted,
        NotificationType::IssueHidden,
    ];

    /**
     * @param  Builder<DomainNotification>  $query
     * @return Builder<DomainNotification>
     */
    public static function apply(Builder $query, User $user): Builder
    {
        $notifyStatus = (bool) ($user->notify_status_changes ?? true);
        $notifyNews = (bool) ($user->notify_district_news ?? true);

        $feedDistrictIds = $user->relationLoaded('feedDistricts')
            ? $user->feedDistricts->pluck('id')->all()
            : $user->feedDistricts()->pluck('districts.id')->all();

        return $query->where(function (Builder $visibility) use ($notifyStatus, $notifyNews, $feedDistrictIds): void {
            $hasCondition = false;

            if ($notifyStatus) {
                $visibility->orWhere(function (Builder $status): void {
                    $status->whereIn('type', self::STATUS_TYPES)
                        ->orWhere(function (Builder $comment): void {
                            $comment->where('type', NotificationType::NewComment)
                                ->whereIn('actor_type', [ActorType::Officer, ActorType::Manager]);
                        });
                });
                $hasCondition = true;
            }

            if ($notifyNews && $feedDistrictIds !== []) {
                $visibility->orWhere(function (Builder $news) use ($feedDistrictIds): void {
                    $news->where('type', NotificationType::NewCommunityPost)
                        ->whereHas('communityPost', function (Builder $post) use ($feedDistrictIds): void {
                            $post->whereIn('district_id', $feedDistrictIds);
                        });
                });
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $visibility->whereRaw('0 = 1');
            }
        });
    }
}
