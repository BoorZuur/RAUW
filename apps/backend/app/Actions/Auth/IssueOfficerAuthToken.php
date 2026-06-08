<?php

namespace App\Actions\Auth;

use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IssueOfficerAuthToken
{
    public function __construct(
        private readonly StartOfficerShift $startOfficerShift,
        private readonly ResolveOfficerTokenHubActive $resolveOfficerTokenHubActive,
    ) {
    }

    public function issue(
        Officer $officer,
        OfficerHubLoginEvaluation $evaluation,
        ?float $latitude,
        ?float $longitude,
        bool $startShift = true,
    ): OfficerAuthTokenResult {
        $isHubActiveEligible = $evaluation->isHubActiveEligible();
        $shouldStartShift = $isHubActiveEligible && $startShift;

        $plainTextToken = DB::transaction(function () use (
            $officer,
            $evaluation,
            $latitude,
            $longitude,
            $isHubActiveEligible,
            $shouldStartShift,
        ) {
            if ($shouldStartShift) {
                $this->startOfficerShift->start($officer);
            }

            $officer->refresh();

            $tokenResult = $shouldStartShift
                ? $officer->createToken('api-login', ['hub-active'])
                : $officer->createToken('api-login');

            OfficerSession::query()->create([
                'officer_id' => $officer->id,
                'personal_access_token_id' => $tokenResult->accessToken->id,
                'hub_id' => $officer->hub_id,
                'shift_start' => now(),
                'start_lat' => $latitude,
                'start_lng' => $longitude,
                'distance_meters_at_login' => $evaluation->distanceMeters,
                'is_hub_active' => $isHubActiveEligible,
                'hub_active_until' => $officer->hub_active_until,
            ]);

            $officer->withAccessToken($tokenResult->accessToken);

            return $tokenResult->plainTextToken;
        });

        $officer->refresh();

        $resolved = $this->resolveOfficerTokenHubActive->resolve($officer);

        $hubActiveUntil = $resolved['hub_active'] && $officer->hub_active_until instanceof Carbon
            ? $officer->hub_active_until
            : null;

        return new OfficerAuthTokenResult(
            plainTextToken: $plainTextToken,
            hubActive: $resolved['hub_active'],
            hubActiveUntil: $hubActiveUntil,
        );
    }
}
