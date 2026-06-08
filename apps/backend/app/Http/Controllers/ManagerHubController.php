<?php

namespace App\Http\Controllers;

use App\Http\Requests\HubAssignments\UpdateManagerHubRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;

class ManagerHubController extends Controller
{
    /**
     * Set an ordinary manager's hub and clear their district assignments.
     *
     * Authorization is enforced by {@see UpdateManagerHubRequest}. Changing the
     * hub invalidates existing district pivots, which are cleared wholesale so
     * district assignments can be re-established within the new hub.
     */
    public function update(UpdateManagerHubRequest $request, Manager $manager): JsonResponse
    {
        $manager->update(['hub_id' => $request->integer('hub_id')]);
        $manager->districts()->sync([]);

        $manager->loadMissing(['departments', 'districts', 'hub']);

        return (new ManagerResource($manager))->response();
    }
}
