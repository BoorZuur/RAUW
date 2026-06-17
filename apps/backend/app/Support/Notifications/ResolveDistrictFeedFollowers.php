<?php

namespace App\Support\Notifications;

use App\Models\CommunityPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResolveDistrictFeedFollowers
{
    /**
     * User ids following the post's district in the community feed.
     *
     * @return Collection<int, int>
     */
    public function userIds(CommunityPost $post): Collection
    {
        if ($post->district_id === null) {
            return collect();
        }

        return DB::table('district_user')
            ->where('district_id', $post->district_id)
            ->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }
}
