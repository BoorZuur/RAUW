<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterUserController extends Controller
{
    /**
     * Register a new user and immediately authenticate them, returning a
     * Sanctum bearer token alongside the canonical safe profile payload.
     *
     * The route is user-specific, so the actor is determined by the route:
     * exactly one User record is created (no Officer or Manager rows), and
     * the response always reports the User actor type. System-managed fields
     * (`is_active`, `flag_count`, `is_under_review`) are left to the model
     * defaults rather than accepted from the client.
     *
     * The response follows the canonical auth contract: token fields plus a
     * single top-level `actor_type` live on the wrapper, while `profile`
     * carries only the cleaned user identity fields (id, name, username,
     * email) defined by {@see AuthProfileResource}.
     */
    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name(),
            'username' => $request->username(),
            'email' => $request->email(),
            'password' => $request->password(),
        ]);

        $token = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'actor_type' => ActorType::User->value,
            'profile' => (new AuthProfileResource($user))->toArray($request),
        ], Response::HTTP_CREATED);
    }
}
