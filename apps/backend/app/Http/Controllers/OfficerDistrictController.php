<?php

namespace App\Http\Controllers;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Requests\DistrictAssignments\UpdateOfficerDistrictsRequest;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

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
     * Authorization is enforced by {@see UpdateOfficerDistrictsRequest}, which
     * restricts this action to an authenticated, active manager; users,
     * officers, and inactive managers all receive a 403. The officer is
     * resolved through route model binding, their `districts()` relation is
     * replaced wholesale with the validated, de-duplicated set, and the
     * response reuses the canonical {@see AuthProfileResource} officer shape.
     *
     * This endpoint only touches the officer-side district pivot; it never
     * modifies `issues.district_id` or reassigns issue districts.
     */
    public function update(UpdateOfficerDistrictsRequest $request, Officer $officer): JsonResponse
    {
        $officer->districts()->sync($request->districtIds());

        // Reload the relations the profile resource embeds so the response
        // reflects the freshly synced districts without lazy queries.
        $officer->load('departments', 'districts', 'hub');

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ]);
    }
}
