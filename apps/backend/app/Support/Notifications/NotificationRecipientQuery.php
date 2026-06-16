<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Enums\NotificationType;
use App\Models\DomainNotification;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class NotificationRecipientQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DomainNotification>
     */
    public static function forUser(User $user, array $filters = []): Builder
    {
        return self::applyFilters(
            DomainNotification::query()->forUser($user),
            $filters,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DomainNotification>
     */
    public static function forOfficer(Officer $officer, array $filters = []): Builder
    {
        return self::applyFilters(
            DomainNotification::query()->forOfficer($officer),
            $filters,
        );
    }

    public static function userOwns(User $user, DomainNotification $notification): bool
    {
        return $notification->recipient_type === ActorType::User
            && $notification->user_id === $user->id;
    }

    public static function officerOwns(Officer $officer, DomainNotification $notification): bool
    {
        return $notification->recipient_type === ActorType::Officer
            && $notification->officer_id === $officer->id;
    }

    /**
     * @param  Builder<DomainNotification>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<DomainNotification>
     */
    private static function applyFilters(Builder $query, array $filters): Builder
    {
        if (array_key_exists('is_read', $filters)) {
            $query->where('is_read', $filters['is_read']);
        }

        if (isset($filters['type'])) {
            $type = $filters['type'];
            $query->where(
                'type',
                $type instanceof NotificationType ? $type : NotificationType::from($type),
            );
        }

        if (isset($filters['since'])) {
            $since = $filters['since'] instanceof Carbon
                ? $filters['since']
                : Carbon::parse($filters['since']);
            $query->since($since);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
