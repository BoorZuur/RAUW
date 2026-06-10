<?php

namespace App\Http\Controllers;

use App\Actions\Auth\CloseOfficerSessions;
use App\Actions\Auth\RevokeOfficerHubActive;
use App\Http\Requests\Officers\DisableOfficerRequest;
use App\Http\Requests\Officers\EnableOfficerRequest;
use App\Http\Requests\Officers\IndexOfficerRequest;
use App\Http\Requests\Officers\ShowOfficerRequest;
use App\Http\Resources\OfficerResource;
use App\Models\Manager;
use App\Models\Officer;
use App\Support\ManagerOfficerHubAccess;
use App\Support\OfficerHubScope;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficerController extends Controller
{
    public function __construct(
        private readonly RevokeOfficerHubActive $revokeOfficerHubActive,
        private readonly CloseOfficerSessions $closeOfficerSessions,
    ) {
    }

    /**
     * List officers with pagination.
     *
     * Authorization is enforced by IndexOfficerRequest, which restricts this
     * action to an authenticated, active officer or manager. Results are
     * hub-scoped for officers and ordinary managers (same hub only; null hub
     * yields no rows); main managers see all officers city-wide. Results
     * exclude soft-deleted officers and default to active officers only when
     * `is_active` is omitted. Optional `district_id` and `department_id`
     * filters narrow the hub-scoped result set through the officer's district
     * and department pivots. Results include eager-loaded departments and
     * districts, ordered by username ascending. Pagination is bounded so
     * `per_page` can never exceed a safe maximum.
     */
    public function index(IndexOfficerRequest $request): AnonymousResourceCollection
    {
        $query = Officer::query()
            ->where('is_active', $request->wantsActiveOfficers());

        $actor = $request->user();
        if ($actor instanceof Officer || $actor instanceof Manager) {
            OfficerHubScope::applyHubScope($query, $actor);
        }

        $officers = $query
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
            ->with(['departments', 'districts', 'hub'])
            ->orderBy('username')
            ->paginate($request->perPage())
            ->withQueryString();

        return OfficerResource::collection($officers);
    }

    /**
     * Show a single officer profile.
     *
     * Authorization is enforced by ShowOfficerRequest, which is available to any
     * authenticated active actor. Results are city-wide with no hub scoping.
     * Soft-deleted officers return 404 from route model binding.
     */
    public function show(ShowOfficerRequest $request, Officer $officer): OfficerResource
    {
        $officer->load(['departments', 'districts', 'hub']);

        return new OfficerResource($officer);
    }

    /**
     * Disable an officer without removing the row.
     *
     * Authorization is enforced by DisableOfficerRequest (active manager only;
     * wrong actor type 403). Hub scoping via ManagerOfficerHubAccess returns
     * 404 when the ordinary manager cannot administer the target officer.
     */
    public function disable(DisableOfficerRequest $request, Officer $officer): OfficerResource
    {
        /** @var Manager $manager */
        $manager = $request->user();
        ManagerOfficerHubAccess::assertManagerCanManageOfficer($manager, $officer);

        $officer->update(['is_active' => false]);

        $this->revokeOfficerHubActive->revoke($officer);
        $this->closeOfficerSessions->closeAllFor($officer);

        $officer->load(['departments', 'districts', 'hub']);

        return new OfficerResource($officer);
    }

    /**
     * Enable an officer without restoring a soft-deleted row.
     *
     * Authorization is enforced by EnableOfficerRequest (active manager only;
     * wrong actor type 403). Hub scoping via ManagerOfficerHubAccess returns
     * 404 when the ordinary manager cannot administer the target officer.
     */
    public function enable(EnableOfficerRequest $request, Officer $officer): OfficerResource
    {
        /** @var Manager $manager */
        $manager = $request->user();
        ManagerOfficerHubAccess::assertManagerCanManageOfficer($manager, $officer);

        $officer->update(['is_active' => true]);

        $officer->load(['departments', 'districts', 'hub']);

        return new OfficerResource($officer);
    }
}
