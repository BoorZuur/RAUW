<?php

namespace App\Http\Controllers;

use App\Http\Requests\HubAssignments\UpdateManagerHubRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;

class MainManagerHubController extends Controller
{
    /**
     * Set a main manager's hub and clear their district assignments.
     *
     * Authorization is enforced by {@see UpdateManagerHubRequest}. Route binding
     * limits `{manager}` to rows with `is_main_manager = true`. Changing the hub
     * clears the district_manager pivot so assignments can be re-established
     * within the new hub.
     */
    public function update(UpdateManagerHubRequest $request, Manager $manager): JsonResponse
    {
        $manager->update(['hub_id' => $request->integer('hub_id')]);
        $manager->districts()->sync([]);

        $manager->loadMissing(['departments', 'districts', 'hub']);

        return (new ManagerResource($manager))->response();
    }
}
