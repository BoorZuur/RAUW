<?php

namespace App\Http\Controllers;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Actions\Auth\EndOfficerShift;
use App\Enums\ActorType;
use App\Http\Requests\HubAssignments\UpdateOfficerHubRequest;
use App\Models\Officer;
use App\Support\ActorDistrictAccess;
use Illuminate\Http\JsonResponse;

/**
 * @group Officers
 */
class OfficerHubController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
        private readonly EndOfficerShift $endOfficerShift,
    ) {
    }

    /**
     * Set an officer's hub and clear their district assignments.
     *
     * Authorization is enforced by {@see UpdateOfficerHubRequest}. Changing the
     * hub invalidates existing district pivots, which are cleared wholesale so
     * district assignments can be re-established within the new hub.
     */
    public function update(UpdateOfficerHubRequest $request, Officer $officer): JsonResponse
    {
        $officer->update(['hub_id' => $request->integer('hub_id')]);
        $officer->districts()->sync([]);
        ActorDistrictAccess::forget($officer);

        $this->endOfficerShift->end($officer);

        $officer->load(['departments', 'districts', 'hub']);

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ]);
    }
}
