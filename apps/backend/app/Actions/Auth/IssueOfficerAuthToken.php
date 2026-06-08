<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Support\Carbon;

class IssueOfficerAuthToken
{
    public function issue(
        Officer $officer,
        OfficerHubLoginEvaluation $evaluation,
        ?float $latitude,
        ?float $longitude,
    ): OfficerAuthTokenResult {
        $isHubActiveEligible = $evaluation->isHubActiveEligible();
        $hubActiveUntil = $isHubActiveEligible
            ? now()->addHours(config('officer.hub_active_ttl_hours'))
            : null;

        $tokenResult = $isHubActiveEligible
            ? $officer->createToken('api-login', ['hub-active'])
            : $officer->createToken('api-login');

        $officer->hub_active_until = $hubActiveUntil;
        $officer->save();

        OfficerSession::query()->create([
            'officer_id' => $officer->id,
            'personal_access_token_id' => $tokenResult->accessToken->id,
            'hub_id' => $officer->hub_id,
            'shift_start' => now(),
            'start_lat' => $latitude,
            'start_lng' => $longitude,
            'distance_meters_at_login' => $evaluation->distanceMeters,
            'is_hub_active' => $isHubActiveEligible,
            'hub_active_until' => $hubActiveUntil,
        ]);

        $hubActive = $isHubActiveEligible
            && $hubActiveUntil instanceof Carbon
            && $hubActiveUntil->isFuture();

        // Attach the new token so AuthProfileResource can derive hub_active via
        // ResolveOfficerTokenHubActive during login/register (no Bearer header yet).
        $officer->withAccessToken($tokenResult->accessToken);

        return new OfficerAuthTokenResult(
            plainTextToken: $tokenResult->plainTextToken,
            hubActive: $hubActive,
            hubActiveUntil: $hubActiveUntil,
        );
    }
}
