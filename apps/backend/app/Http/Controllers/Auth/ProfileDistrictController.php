<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DistrictAssignments\UpdateOwnDistrictsRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

class ProfileDistrictController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Sync the authenticated actor's own district assignments and return the
     * refreshed canonical auth profile payload.
     *
     * Authorization is enforced by {@see UpdateOwnDistrictsRequest}, which
     * restricts this action to an authenticated, active manager or officer.
     * Users (who have no district relationship) and any unsupported actor are
     * rejected with a 403 before this method runs. The actor's `districts()`
     * relation is replaced wholesale with the validated, de-duplicated set, and
     * the response reuses the same {@see AuthProfileResource} shape as
     * `GET /api/auth/me`, with `actor_type` on the wrapper.
     *
     * This endpoint only touches actor-side district pivots; it never modifies
     * `issues.district_id` or reassigns issue districts.
     */
    public function __invoke(UpdateOwnDistrictsRequest $request): JsonResponse
    {
        /** @var Manager|Officer $actor */
        $actor = $request->user();

        $actor->districts()->sync($request->districtIds());

        // Reload the relations the profile resource embeds so the response
        // reflects the freshly synced districts without lazy queries.
        $actor->load('departments', 'districts', 'hub');

        $type = $actor instanceof Manager ? ActorType::Manager : ActorType::Officer;

        return response()->json([
            'actor_type' => $type->value,
            'profile' => $actor instanceof Officer
                ? $this->buildOfficerAuthProfile->build($actor, $request)
                : (new AuthProfileResource($actor))->toArray($request),
        ]);
    }
}
