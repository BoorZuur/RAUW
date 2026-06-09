<?php

namespace App\Support;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OfficerHubScope
{
    /**
     * Restrict officer queries to the actor's hub.
     *
     * Main managers: unchanged (city-wide). Others: hub_id match; null hub_id → no rows.
     */
    public static function applyHubScope(Builder $query, Officer|Manager $actor): Builder
    {
        if ($actor instanceof Manager && ActorDistrictAccess::isMainManager($actor)) {
            return $query;
        }

        if ($actor->hub_id === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('hub_id', $actor->hub_id);
    }

    /**
     * Whether the actor may view a target in the same hub (manager show endpoints).
     *
     * Both hub_id must be non-null and equal. No main-manager bypass.
     */
    public static function actorCanViewInHub(Officer|Manager $actor, Model $target): bool
    {
        return $actor->hub_id !== null
            && $target->hub_id !== null
            && (int) $actor->hub_id === (int) $target->hub_id;
    }
}
