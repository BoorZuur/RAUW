<?php

namespace App\Support;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommunityPostFeedQuery
{
    /**
     * Build the initial feed query based on the actor.
     *
     * @param Model $actor The User, Officer, or Manager
     */
    public function build(Model $actor): Builder
    {
        $query = \App\Models\CommunityPost::query();

        if ($actor instanceof User) {
            // Users see visible posts in their feed districts
            $query->where('visibility', 'visible')
                ->whereIn('district_id', $actor->feedDistricts()->select('districts.id'));
            return $query;
        }

        if ($actor instanceof Officer) {
            // Officers see posts in their assigned districts plus their own orphaned posts
            $districtIds = $actor->districts()->select('districts.id');
            $query->where(function ($q) use ($districtIds, $actor) {
                $q->whereIn('district_id', $districtIds)
                  ->orWhere('officer_id', $actor->id);
            });
            return $query;
        }

        if ($actor instanceof Manager) {
            // Managers see everything
            return $query;
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Apply feed scope to a query based on the actor.
     *
     * @param Builder $query The CommunityPost query builder
     * @param Model $actor The User, Officer, or Manager
     * @param int|null $requestedDistrictId Optional district ID (required for users, optional filter for others)
     */
    public static function applyFeedScope(Builder $query, Model $actor, ?int $requestedDistrictId = null): Builder
    {
        if ($actor instanceof User) {
            // User must provide a district_id (validated in Request to be in their feed and active)
            return $query->where('district_id', $requestedDistrictId);
        }

        if ($actor instanceof Officer) {
            // Officer defaults to posts in their assigned districts (excluding orphans).
            // If requestedDistrictId is provided, the Request validation ensures it's one of their assigned districts.
            if ($requestedDistrictId !== null) {
                return $query->where('district_id', $requestedDistrictId);
            }

            $query = ActorDistrictAccess::applyDistrictScope($query, $actor);
            return $query->whereNotNull('district_id');
        }

        if ($actor instanceof Manager) {
            // Managers get app-wide posts (including orphans) by default.
            if ($requestedDistrictId !== null) {
                return $query->where('district_id', $requestedDistrictId);
            }

            return $query;
        }

        return $query->whereRaw('0 = 1');
    }
}
