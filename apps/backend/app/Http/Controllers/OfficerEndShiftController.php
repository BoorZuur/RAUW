<?php

namespace App\Http\Controllers;

use App\Actions\Auth\EndOfficerShift;
use App\Http\Requests\Officers\EndOfficerShiftRequest;
use App\Http\Resources\OfficerResource;
use App\Models\Officer;

class OfficerEndShiftController extends Controller
{
    public function __construct(
        private readonly EndOfficerShift $endOfficerShift,
    ) {
    }

    /**
     * End an officer's shared shift without revoking tokens.
     *
     * Authorization is enforced by {@see EndOfficerShiftRequest}, which
     * restricts this action to an authenticated, active manager.
     */
    public function __invoke(EndOfficerShiftRequest $request, Officer $officer): OfficerResource
    {
        $this->endOfficerShift->end($officer);

        $officer->refresh();
        $officer->load(['departments', 'districts', 'hub']);

        return new OfficerResource($officer);
    }
}
