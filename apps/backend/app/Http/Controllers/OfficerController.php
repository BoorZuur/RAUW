<?php

namespace App\Http\Controllers;

use App\Http\Requests\Officers\DisableOfficerRequest;
use App\Http\Requests\Officers\EnableOfficerRequest;
use App\Http\Requests\Officers\IndexOfficerRequest;
use App\Http\Resources\OfficerResource;
use App\Models\Officer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficerController extends Controller
{
    /**
     * List officers with pagination.
     *
     * Authorization is enforced by IndexOfficerRequest, which restricts this
     * action to an authenticated, active officer or manager. Results exclude
     * soft-deleted officers and default to active officers only when `is_active`
     * is omitted. Optional `district_id` and `department_id` filters narrow the
     * result set through the officer's district and department pivots. Results
     * include eager-loaded departments and districts, ordered by username
     * ascending. Pagination is bounded so `per_page` can never exceed a safe
     * maximum.
     */
    public function index(IndexOfficerRequest $request): AnonymousResourceCollection
    {
        $officers = Officer::query()
            ->where('is_active', $request->wantsActiveOfficers())
            ->when(
                $request->filled('district_id'),
                fn ($query) => $query->whereHas(
                    'districts',
                    fn ($districtQuery) => $districtQuery->where(
                        'districts.id',
                        $request->integer('district_id'),
                    ),
                ),
            )
            ->when(
                $request->filled('department_id'),
                fn ($query) => $query->whereHas(
                    'departments',
                    fn ($departmentQuery) => $departmentQuery->where(
                        'departments.id',
                        $request->integer('department_id'),
                    ),
                ),
            )
            ->with(['departments', 'districts'])
            ->orderBy('username')
            ->paginate($request->perPage())
            ->withQueryString();

        return OfficerResource::collection($officers);
    }

    /**
     * Disable an officer without removing the row.
     *
     * Authorization is enforced by DisableOfficerRequest, which restricts this
     * action to an authenticated, active manager. Setting `is_active = false`
     * preserves the officer record and historical associations without
     * soft-deleting the row.
     */
    public function disable(DisableOfficerRequest $request, Officer $officer): OfficerResource
    {
        $officer->update(['is_active' => false]);

        $officer->load(['departments', 'districts']);

        return new OfficerResource($officer);
    }

    /**
     * Enable an officer without restoring a soft-deleted row.
     *
     * Authorization is enforced by EnableOfficerRequest, which restricts this
     * action to an authenticated, active manager. Setting `is_active = true`
     * reactivates the officer. Re-enabling an already active officer is
     * idempotent and returns 200.
     */
    public function enable(EnableOfficerRequest $request, Officer $officer): OfficerResource
    {
        $officer->update(['is_active' => true]);

        $officer->load(['departments', 'districts']);

        return new OfficerResource($officer);
    }
}
