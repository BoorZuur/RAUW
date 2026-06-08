<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentAssignments\UpdateManagerDepartmentsRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;

class ManagerDepartmentController extends Controller
{
    /**
     * Sync a target manager's department assignments on behalf of an active main
     * manager and return that manager's refreshed profile payload.
     *
     * Authorization is enforced by {@see UpdateManagerDepartmentsRequest}. The
     * manager is resolved through route model binding, their `departments()`
     * relation is replaced wholesale with the validated set, and the response
     * reuses {@see ManagerResource}, matching manager creation responses.
     *
     * This endpoint only touches the `department_manager` pivot; it never
     * modifies district assignments, credentials, or privileged flags.
     */
    public function update(UpdateManagerDepartmentsRequest $request, Manager $manager): JsonResponse
    {
        $manager->departments()->sync($request->departmentIds());

        $manager->loadMissing('departments', 'districts');

        return (new ManagerResource($manager))->response();
    }
}
