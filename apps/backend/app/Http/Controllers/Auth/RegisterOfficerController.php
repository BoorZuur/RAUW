<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterOfficerRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterOfficerController extends Controller
{
    /**
     * Register a new officer and immediately authenticate them, returning a
     * Sanctum bearer token alongside the canonical safe profile payload.
     *
     * The route is officer-specific, so the actor is determined by the route:
     * exactly one Officer record is created (no User or Manager rows), and the
     * response always reports the Officer actor type. `district_id` is left
     * unset (nullable) and `is_active` defaults to true via the model.
     *
     * The response follows the canonical auth contract: token fields plus a
     * single top-level `actor_type` live on the wrapper, while `profile`
     * carries only the cleaned officer identity fields (id, username, email,
     * badge_number, district?) defined by {@see AuthProfileResource}.
     */
    public function __invoke(RegisterOfficerRequest $request): JsonResponse
    {
        $officer = Officer::create([
            'username' => $request->username(),
            'email' => $request->email(),
            'password' => $request->password(),
            'badge_number' => $request->badgeNumber(),
        ]);

        // Eager-load the compact district relation so AuthProfileResource embeds
        // the same district key behavior as login (null when unassigned).
        $officer->loadMissing('district');

        $token = $officer->createToken('api-login')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'actor_type' => ActorType::Officer->value,
            'profile' => (new AuthProfileResource($officer))->toArray($request),
        ], Response::HTTP_CREATED);
    }
}
