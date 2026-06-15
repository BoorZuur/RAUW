<?php

namespace App\Actions\Districts;

use App\Models\District;
use Illuminate\Support\Facades\DB;

class DetachDistrictFromCommunityFeeds
{
    /**
     * Detach all users from this district in their feed, and orphan any community posts.
     * This is called when a district is deactivated or about to be deleted.
     */
    public function handle(District $district): void
    {
        DB::transaction(function () use ($district) {
            // Remove all users from this district feed pivot
            $district->feedUsers()->detach();

            // Null out the district ID on any community posts in this district
            $district->communityPosts()->update(['district_id' => null]);
        });
    }
}
