<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Actions\Auth\EvaluateOfficerHubLogin;
use App\Actions\Auth\ResolveOfficerTokenHubActive;
use App\Actions\Auth\StartOfficerShift;
use App\Enums\OfficerHubLoginEligibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StartOfficerShiftRequest;
use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @group Authentication
 */
class StartOfficerShiftController extends Controller
{
    public function __construct(
        private readonly EvaluateOfficerHubLogin $evaluateOfficerHubLogin,
        private readonly StartOfficerShift $startOfficerShift,
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
        private readonly ResolveOfficerTokenHubActive $resolveOfficerTokenHubActive,
    ) {
    }

    /**
     * Start the officer's shared hub shift when at the assigned hub and no
     * shift is currently active.
     */
    public function __invoke(StartOfficerShiftRequest $request): JsonResponse
    {
        /** @var Officer $officer */
        $officer = $request->user();
        $officer->loadMissing(['departments', 'districts', 'hub']);

        $evaluation = $this->evaluateOfficerHubLogin->evaluate(
            $officer,
            $request->latitude(),
            $request->longitude(),
        );

        $eligibilityResponse = $this->eligibilityResponse($evaluation->eligibility);

        if ($eligibilityResponse !== null) {
            return $eligibilityResponse;
        }

        $officer->refresh();

        if ($officer->hub_active_until?->isFuture()) {
            return response()->json([
                'message' => 'Officer shift is already active.',
                'code' => 'shift_already_active',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $started = DB::transaction(function () use ($officer, $request, $evaluation): bool {
            $shiftResult = $this->startOfficerShift->start($officer);

            if (! $shiftResult->started) {
                return false;
            }

            $officer->refresh();

            $token = $officer->currentAccessToken();

            if ($token !== null && ! $token->can('hub-active')) {
                $abilities = $token->abilities ?? [];

                if (! in_array('hub-active', $abilities, true)) {
                    $token->forceFill([
                        'abilities' => array_merge($abilities, ['hub-active']),
                    ])->save();
                }
            }

            OfficerSession::query()->create([
                'officer_id' => $officer->id,
                'personal_access_token_id' => $token?->id,
                'hub_id' => $officer->hub_id,
                'shift_start' => now(),
                'start_lat' => $request->latitude(),
                'start_lng' => $request->longitude(),
                'distance_meters_at_login' => $evaluation->distanceMeters,
                'is_hub_active' => true,
                'hub_active_until' => $officer->hub_active_until,
            ]);

            return true;
        });

        if (! $started) {
            return response()->json([
                'message' => 'Officer shift is already active.',
                'code' => 'shift_already_active',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $officer->refresh();
        $officer->loadMissing(['departments', 'districts', 'hub']);

        $resolved = $this->resolveOfficerTokenHubActive->resolve($officer);

        return response()->json([
            'hub_active' => $resolved['hub_active'],
            'hub_active_until' => $resolved['hub_active_until'],
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ]);
    }

    private function eligibilityResponse(OfficerHubLoginEligibility $eligibility): ?JsonResponse
    {
        return match ($eligibility) {
            OfficerHubLoginEligibility::HubNotAssigned,
            OfficerHubLoginEligibility::HubNotFound => response()->json([
                'message' => 'Officer hub assignment required before login.',
                'code' => 'hub_not_assigned',
            ], Response::HTTP_FORBIDDEN),
            OfficerHubLoginEligibility::OutsideRadius,
            OfficerHubLoginEligibility::InactiveHub,
            OfficerHubLoginEligibility::MissingCoordinates => response()->json([
                'message' => 'Start shift requires login within hub radius.',
                'code' => 'outside_hub_radius',
            ], Response::HTTP_FORBIDDEN),
            OfficerHubLoginEligibility::MissingLoginCoordinates => response()->json([
                'message' => 'Latitude and longitude are required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY),
            OfficerHubLoginEligibility::HubActiveEligible => null,
        };
    }
}
