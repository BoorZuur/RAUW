<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use Illuminate\Support\Carbon;

class StartOfficerShift
{
    /**
     * Start a shared shift when none is active. Past {@see Officer::$hub_active_until}
     * is treated as inactive and normalized to null before starting a new shift.
     */
    public function start(Officer $officer): StartOfficerShiftResult
    {
        $officer->refresh();

        $hubActiveUntil = $officer->hub_active_until;

        if ($hubActiveUntil instanceof Carbon && $hubActiveUntil->isFuture()) {
            return new StartOfficerShiftResult(
                started: false,
                hubActiveUntil: $hubActiveUntil,
            );
        }

        if ($hubActiveUntil !== null && $hubActiveUntil->isPast()) {
            $officer->hub_active_until = null;
        }

        $hubActiveUntil = now()->addHours(config('officer.hub_active_ttl_hours'));
        $officer->hub_active_until = $hubActiveUntil;
        $officer->save();

        return new StartOfficerShiftResult(
            started: true,
            hubActiveUntil: $hubActiveUntil,
        );
    }
}
