<?php

namespace App\Support;

use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ManagerOfficerHubAccess
{
    /**
     * Whether the manager may administer the officer (disable, enable, end-shift, districts).
     *
     * Main managers: always true. Ordinary managers: both hub_id non-null and equal.
     */
    public static function managerCanManageOfficer(Manager $manager, Officer $officer): bool
    {
        if (ActorDistrictAccess::isMainManager($manager)) {
            return true;
        }

        return $manager->hub_id !== null
            && $officer->hub_id !== null
            && (int) $manager->hub_id === (int) $officer->hub_id;
    }

    /**
     * Assert the manager may administer the officer or abort 404.
     *
     * Hub mismatch returns 404 (not 403) to avoid leaking officer existence.
     * Main managers always pass.
     */
    public static function assertManagerCanManageOfficer(Manager $manager, Officer $officer): void
    {
        if (! self::managerCanManageOfficer($manager, $officer)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Officer not found.',
                ], Response::HTTP_NOT_FOUND)
            );
        }
    }
}
