<?php

namespace App\Support;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UserCommunityPostDistrictAccess
{
    /**
     * @var array<string, array<int>>
     */
    private static array $enabledDistrictIdsCache = [];

    /**
     * Get IDs of all active feed districts for this user.
     *
     * @return array<int>
     */
    public static function enabledDistrictIds(User $user): array
    {
        $cacheKey = $user->getKey();

        if (! array_key_exists($cacheKey, self::$enabledDistrictIdsCache)) {
            if ($user->relationLoaded('feedDistricts')) {
                self::$enabledDistrictIdsCache[$cacheKey] = $user->feedDistricts
                    ->where('is_active', true)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
            } else {
                self::$enabledDistrictIdsCache[$cacheKey] = array_map(
                    'intval',
                    $user->feedDistricts()->where('districts.is_active', true)->pluck('districts.id')->all()
                );
            }
        }

        return self::$enabledDistrictIdsCache[$cacheKey];
    }

    /**
     * Whether the user has the district in their feed and the district is active.
     */
    public static function userHasFeedDistrict(User $user, int $districtId): bool
    {
        return in_array($districtId, self::enabledDistrictIds($user), true);
    }

    /**
     * Whether the user can view the post based on district feed rules.
     * Post must have a district, and it must be in the user's enabled feed districts.
     */
    public static function userCanViewPostDistrict(User $user, CommunityPost $post): bool
    {
        if ($post->district_id === null) {
            return false;
        }

        return self::userHasFeedDistrict($user, (int) $post->district_id);
    }

    /**
     * Assert the user has the given active feed district or abort 403.
     */
    public static function assertUserHasFeedDistrict(User $user, int $districtId): void
    {
        if (! self::userHasFeedDistrict($user, $districtId)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'District is not enabled in user feed.',
                    'code' => 'district_not_enabled',
                ], Response::HTTP_FORBIDDEN)
            );
        }
    }
}
