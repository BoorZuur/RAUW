<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Actions\Auth\EvaluateOfficerHubLogin;
use App\Actions\Auth\IssueOfficerAuthToken;
use App\Actions\Auth\OfficerAuthTokenResult;
use App\Actions\Auth\ResolveLoginActor;
use App\Enums\ActorType;
use App\Enums\OfficerHubLoginEligibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Manager;
use App\Models\Officer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @group Authentication
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly ResolveLoginActor $resolver,
        private readonly EvaluateOfficerHubLogin $evaluateOfficerHubLogin,
        private readonly IssueOfficerAuthToken $issueOfficerAuthToken,
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Authenticate a user, officer, or manager with shared email/password
     * credentials and return a Sanctum bearer token alongside the actor's
     * canonical safe profile payload.
     *
     * The response follows the canonical auth contract: token fields
     * (`token_type`, `access_token`) and a single top-level `actor_type` live
     * on the wrapper, while `profile` carries only the cleaned, client-facing
     * identity fields defined by {@see AuthProfileResource}. `actor_type` is
     * not duplicated inside `profile`.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $match = $this->resolver->resolve($request->email(), $request->password());
        } catch (AuthenticationException) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var \Illuminate\Database\Eloquent\Model&\Laravel\Sanctum\HasApiTokens $actor */
        $actor = $match['actor'];
        /** @var ActorType $type */
        $type = $match['type'];

        // Eager-load the compact districts relation for officers and managers
        // so the profile resource can embed them without triggering lazy queries.
        if ($actor instanceof Officer || $actor instanceof Manager) {
            $actor->loadMissing(['districts', 'hub']);
        } elseif ($actor instanceof \App\Models\User) {
            $actor->loadMissing('feedDistricts');
        }

        // Eager-load the actor's department relationships so the profile
        // resource can embed them without triggering lazy queries: both
        // managers and officers have one or more `departments`.
        if ($actor instanceof Manager && ! $actor->relationLoaded('departments')) {
            $actor->loadMissing('departments');
        }

        if ($actor instanceof Officer && ! $actor->relationLoaded('departments')) {
            $actor->loadMissing('departments');
        }

        if ($actor instanceof Officer) {
            return $this->loginOfficer($request, $actor, $type);
        }

        $token = $actor->createToken('api-login')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'actor_type' => $type->value,
            'profile' => (new AuthProfileResource($actor))->toArray($request),
        ]);
    }

    private function loginOfficer(LoginRequest $request, Officer $officer, ActorType $type): JsonResponse
    {
        $request->validateOfficerCoordinates();

        $evaluation = $this->evaluateOfficerHubLogin->evaluate(
            $officer,
            $request->latitude(),
            $request->longitude(),
        );

        if (in_array($evaluation->eligibility, [
            OfficerHubLoginEligibility::HubNotAssigned,
            OfficerHubLoginEligibility::HubNotFound,
        ], true)) {
            return response()->json([
                'message' => 'Officer hub assignment required before login.',
                'code' => 'hub_not_assigned',
            ], Response::HTTP_FORBIDDEN);
        }

        $authToken = $this->issueOfficerAuthToken->issue(
            $officer,
            $evaluation,
            $request->latitude(),
            $request->longitude(),
        );

        return response()->json(
            $this->officerAuthResponse($request, $officer, $type, $authToken),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function officerAuthResponse(
        LoginRequest $request,
        Officer $officer,
        ActorType $type,
        OfficerAuthTokenResult $authToken,
    ): array {
        return [
            'token_type' => 'Bearer',
            'access_token' => $authToken->plainTextToken,
            'actor_type' => $type->value,
            'hub_active' => $authToken->hubActive,
            'hub_active_until' => $authToken->hubActiveUntil?->toIso8601String(),
            'profile' => $this->buildOfficerAuthProfile->build($officer, $request),
        ];
    }
}
