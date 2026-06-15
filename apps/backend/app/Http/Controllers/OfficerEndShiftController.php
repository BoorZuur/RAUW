<?php

namespace App\Http\Controllers;

use App\Actions\Auth\EndOfficerShift;
use App\Http\Requests\Officers\EndOfficerShiftRequest;
use App\Http\Resources\OfficerResource;
use App\Models\Manager;
use App\Models\Officer;
use App\Support\ManagerOfficerHubAccess;

/**
 * @group Officers
 */
class OfficerEndShiftController extends Controller
{
    public function __construct(
        private readonly EndOfficerShift $endOfficerShift,
    ) {
    }

    /**
     * End an officer's shared shift without revoking tokens.
     *
     * Authorization is enforced by {@see EndOfficerShiftRequest} (active
     * manager only; wrong actor type 403). Hub scoping via
     * ManagerOfficerHubAccess returns 404 when the ordinary manager cannot
     * administer the target officer.
     */
    public function __invoke(EndOfficerShiftRequest $request, Officer $officer): OfficerResource
    {
        /** @var Manager $manager */
        $manager = $request->user();
        ManagerOfficerHubAccess::assertManagerCanManageOfficer($manager, $officer);

        $this->endOfficerShift->end($officer);

        $officer->refresh();
        $officer->load(['departments', 'districts', 'hub']);

        return new OfficerResource($officer);
    }
}
