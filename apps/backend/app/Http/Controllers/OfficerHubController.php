<?php

namespace App\Http\Controllers;

use App\Enums\ActorType;
use App\Http\Requests\HubAssignments\UpdateOfficerHubRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

class OfficerHubController extends Controller
{
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

        $officer->load(['departments', 'districts', 'hub']);

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => (new AuthProfileResource($officer))->toArray($request),
        ]);
    }
}
