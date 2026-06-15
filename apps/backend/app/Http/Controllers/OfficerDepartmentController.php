<?php

namespace App\Http\Controllers;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Requests\DepartmentAssignments\UpdateOfficerDepartmentsRequest;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

/**
 * @group Officers
 */
class OfficerDepartmentController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Sync a target officer's department assignments on behalf of an active main
     * manager and return the officer's refreshed profile payload.
     *
     * Authorization is enforced by {@see UpdateOfficerDepartmentsRequest}. The
     * officer is resolved through route model binding, their `departments()`
     * relation is replaced wholesale with the validated set, and the response
     * reuses the canonical {@see AuthProfileResource} officer shape.
     *
     * This endpoint only touches the `department_officer` pivot; it never
     * modifies district assignments or issue data.
     */
    public function update(UpdateOfficerDepartmentsRequest $request, Officer $officer): JsonResponse
    {
        $officer->departments()->sync($request->departmentIds());

        $officer->load('departments', 'districts', 'hub');

        return response()->json([
            'actor_type' => ActorType::Officer->value,
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ]);
    }
}
