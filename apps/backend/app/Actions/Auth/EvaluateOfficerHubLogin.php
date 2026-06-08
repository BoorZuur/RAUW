<?php

namespace App\Actions\Auth;

use App\Enums\OfficerHubLoginEligibility;
use App\Models\Hub;
use App\Models\Officer;
use App\Support\Geo\Haversine;

class EvaluateOfficerHubLogin
{
    public function evaluate(Officer $officer, ?float $latitude, ?float $longitude): OfficerHubLoginEvaluation
    {
        if ($latitude === null || $longitude === null) {
            return new OfficerHubLoginEvaluation(OfficerHubLoginEligibility::MissingLoginCoordinates);
        }

        if ($officer->hub_id === null) {
            return new OfficerHubLoginEvaluation(OfficerHubLoginEligibility::HubNotAssigned);
        }

        $hub = $officer->relationLoaded('hub') ? $officer->hub : $officer->hub()->first();

        if (! $hub instanceof Hub) {
            return new OfficerHubLoginEvaluation(OfficerHubLoginEligibility::HubNotFound);
        }

        if ($hub->latitude === null || $hub->longitude === null) {
            return new OfficerHubLoginEvaluation(OfficerHubLoginEligibility::MissingCoordinates);
        }

        if (! $hub->is_active) {
            return new OfficerHubLoginEvaluation(OfficerHubLoginEligibility::InactiveHub);
        }

        $distanceMeters = (int) round(Haversine::distanceMeters(
            $latitude,
            $longitude,
            (float) $hub->latitude,
            (float) $hub->longitude,
        ));

        if ($distanceMeters > $hub->radius_meters) {
            return new OfficerHubLoginEvaluation(
                OfficerHubLoginEligibility::OutsideRadius,
                $distanceMeters,
            );
        }

        return new OfficerHubLoginEvaluation(
            OfficerHubLoginEligibility::HubActiveEligible,
            $distanceMeters,
        );
    }
}
