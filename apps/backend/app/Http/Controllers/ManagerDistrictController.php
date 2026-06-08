<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistrictAssignments\UpdateManagerDistrictsRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;

class ManagerDistrictController extends Controller
{
    /**
     * Sync a target manager's district assignments on behalf of an active main
     * manager and return that manager's refreshed profile payload.
     *
     * Authorization is enforced by {@see UpdateManagerDistrictsRequest}. The
     * manager is resolved through route model binding, their `districts()`
     * relation is replaced wholesale with the validated set, and the response
     * reuses {@see ManagerResource}, matching manager creation responses.
     *
     * This endpoint only touches the `district_manager` pivot; it never
     * modifies department assignments, credentials, privileged flags, or
     * `issues.district_id`.
     */
    public function update(UpdateManagerDistrictsRequest $request, Manager $manager): JsonResponse
    {
        $manager->districts()->sync($request->districtIds());

        $manager->loadMissing('departments', 'districts');

        return (new ManagerResource($manager))->response();
    }
}
