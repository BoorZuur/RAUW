<?php

namespace App\Http\Controllers;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Requests\DistrictAssignments\UpdateOfficerDistrictsRequest;
use App\Models\Manager;
use App\Models\Officer;
use App\Support\ActorDistrictAccess;
use App\Support\ManagerOfficerHubAccess;
use Illuminate\Http\JsonResponse;

/**
 * @group Officers
 */
class OfficerDistrictController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Sync a target officer's district assignments on behalf of an active
     * manager and return the officer's refreshed profile payload.
     *
     * Authorization is enforced by {@see UpdateOfficerDistrictsRequest} (active
     * manager only; wrong actor type 403). Hub scoping via
     * ManagerOfficerHubAccess returns 404 when the ordinary manager cannot
     * administer the target officer. The officer is
     * resolved through route model binding, their `districts()` relation is
     * replaced wholesale with the validated, de-duplicated set, and the
     * response reuses the canonical {@see AuthProfileResource} officer shape.
     *
     * This endpoint only touches the officer-side district pivot; it never
     * modifies `issues.district_id` or reassigns issue districts.
     */
    public function update(UpdateOfficerDistrictsRequest $request, Officer $officer): JsonResponse
    {
        /** @var Manager $manager */
        $manager = $request->user();
        ManagerOfficerHubAccess::assertManagerCanManageOfficer($manager, $officer);

        $officer->districts()->sync($request->districtIds());
        ActorDistrictAccess::forget($officer);

        // Reload the relations the profile resource embeds so the response
        // reflects the freshly synced districts without lazy queries.
        $officer->load('departments', 'districts', 'hub');

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ]);
    }
}
