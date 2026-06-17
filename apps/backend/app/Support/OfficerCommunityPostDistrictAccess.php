<?php

namespace App\Support;

use App\Models\CommunityPost;
use App\Models\Officer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class OfficerCommunityPostDistrictAccess
{
    /**
     * Whether the officer is assigned to the post's district via district_officer.
     */
    public static function officerInPostDistrict(Officer $officer, CommunityPost $post): bool
    {
        return ActorDistrictAccess::actorInPostDistrict($officer, $post);
    }

    /**
     * Whether the officer is assigned to the given district ID.
     */
    public static function officerInDistrict(Officer $officer, int $districtId): bool
    {
        return in_array($districtId, ActorDistrictAccess::assignedDistrictIds($officer), true);
    }

    /**
     * Assert the officer is assigned to the post's district or abort 403.
     */
    public static function assertOfficerInPostDistrict(Officer $officer, CommunityPost $post): void
    {
        if (! self::officerInPostDistrict($officer, $post)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Officer is not assigned to this post district.',
                    'code' => 'officer_not_in_district',
                ], Response::HTTP_FORBIDDEN)
            );
        }
    }
}
