<?php

namespace App\Support;

use App\Enums\Visibility;
use App\Models\CommunityPost;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommunityPostVisibilityQuery
{
    /**
     * Determine if the actor can view the post based on visibility and their role.
     */
    public static function canViewPost(CommunityPost $post, Model $actor): bool
    {
        if ($actor instanceof User) {
            return $post->visibility === Visibility::Visible && UserCommunityPostDistrictAccess::userCanViewPostDistrict($actor, $post);
        }

        if ($actor instanceof Officer) {
            // Author can always see their own posts, including orphaned ones.
            if ($post->officer_id === $actor->id) {
                return true;
            }

            // Other officers can only see posts in their assigned districts.
            if ($post->district_id !== null) {
                return OfficerCommunityPostDistrictAccess::officerInPostDistrict($actor, $post);
            }

            return false;
        }

        if ($actor instanceof Manager) {
            // Managers can view any post (main managers see all, ordinary managers see all via index but
            // might be restricted on visibility edits. For viewing, we allow managers to see them).
            return true;
        }

        return false;
    }

    /**
     * Apply visibility scope to a query based on the actor.
     */
    public static function applyVisibilityScope(Builder $query, Model $actor): Builder
    {
        if ($actor instanceof User) {
            return $query->where('visibility', Visibility::Visible->value);
        }

        // Officers and Managers are unscoped by visibility (can see both visible and hidden).
        return $query;
    }
}
