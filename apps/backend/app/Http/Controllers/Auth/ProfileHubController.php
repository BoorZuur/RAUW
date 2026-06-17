<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Actions\Auth\EndOfficerShift;
use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HubAssignments\UpdateOwnHubRequest;
use App\Models\Officer;
use App\Support\ActorDistrictAccess;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 */
class ProfileHubController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
        private readonly EndOfficerShift $endOfficerShift,
    ) {
    }

    /**
     * Set the authenticated officer's hub and clear their district assignments.
     *
     * Authorization is enforced by {@see UpdateOwnHubRequest}, which restricts
     * this action to an authenticated, active officer. Changing the hub invalidates
     * existing district pivots, which are cleared wholesale.
     */
    public function __invoke(UpdateOwnHubRequest $request): JsonResponse
    {
        /** @var Officer $actor */
        $actor = $request->user();

        $actor->update(['hub_id' => $request->integer('hub_id')]);
        $actor->districts()->sync([]);
        ActorDistrictAccess::forget($actor);

        $this->endOfficerShift->end($actor);

        $actor->load(['departments', 'districts', 'hub']);

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => $this->buildOfficerAuthProfile->build($actor, $request),
        ]);
    }
}
