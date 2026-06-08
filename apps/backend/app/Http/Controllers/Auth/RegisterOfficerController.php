<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\EvaluateOfficerHubLogin;
use App\Actions\Auth\IssueOfficerAuthToken;
use App\Actions\Auth\OfficerAuthTokenResult;
use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterOfficerRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterOfficerController extends Controller
{
    public function __construct(
        private readonly EvaluateOfficerHubLogin $evaluateOfficerHubLogin,
        private readonly IssueOfficerAuthToken $issueOfficerAuthToken,
    ) {
    }

    /**
     * Register a new officer and immediately authenticate them, returning a
     * Sanctum bearer token alongside the canonical safe profile payload.
     *
     * The route is officer-specific, so the actor is determined by the route:
     * exactly one Officer record is created (no User or Manager rows), and the
     * response always reports the Officer actor type. District assignments are
     * optional at registration: new officers may start with no districts or an
     * optional, validated `district_ids` array synced through the pivot, and
     * `is_active` defaults to true via the model.
     *
     * The response follows the canonical auth contract: token fields plus a
     * single top-level `actor_type` live on the wrapper, while `profile`
     * carries only the cleaned officer identity fields (id, username, email,
     * badge_number, districts) defined by {@see AuthProfileResource}.
     */
    public function __invoke(RegisterOfficerRequest $request): JsonResponse
    {
        $officer = Officer::create([
            'username' => $request->username(),
            'email' => $request->email(),
            'password' => $request->password(),
            'badge_number' => $request->badgeNumber(),
        ]);

        // Persist the officer's one-or-more department assignments in the pivot.
        $officer->departments()->sync($request->departmentIds());

        // Persist any optional district assignments through the pivot. New
        // officers may register with no districts at all.
        $officer->districts()->sync($request->districtIds());

        // Eager-load the compact district and department relations so
        // AuthProfileResource embeds the same keys as login without lazy queries.
        $officer->loadMissing('departments', 'districts', 'hub');

        $evaluation = $this->evaluateOfficerHubLogin->evaluate(
            $officer,
            $request->latitude(),
            $request->longitude(),
        );

        $authToken = $this->issueOfficerAuthToken->issue(
            $officer,
            $evaluation,
            $request->latitude(),
            $request->longitude(),
        );

        return response()->json(
            $this->officerAuthResponse($request, $officer, $authToken),
            Response::HTTP_CREATED,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function officerAuthResponse(
        RegisterOfficerRequest $request,
        Officer $officer,
        OfficerAuthTokenResult $authToken,
    ): array {
        return [
            'token_type' => 'Bearer',
            'access_token' => $authToken->plainTextToken,
            'actor_type' => ActorType::Officer->value,
            'hub_active' => $authToken->hubActive,
            'hub_active_until' => $authToken->hubActiveUntil?->toIso8601String(),
            'profile' => (new AuthProfileResource($officer))->toArray($request),
        ];
    }
}
