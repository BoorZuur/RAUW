<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthProfileResource;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Return the authenticated actor's canonical profile payload using the
     * same shape as the shared login/register responses, minus the token
     * fields.
     *
     * Per the canonical auth contract, a single top-level `actor_type` is
     * emitted and `profile` reuses the same cleaned {@see AuthProfileResource}
     * shape as login/register, so `GET /api/auth/me` stays in lockstep with
     * the token-issuing endpoints.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $actor = $request->user();

        $type = match (true) {
            $actor instanceof User => ActorType::User,
            $actor instanceof Officer => ActorType::Officer,
            $actor instanceof Manager => ActorType::Manager,
            default => null,
        };

        if ($type === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Eager-load the compact districts relation for officers and managers
        // so the profile resource can embed them without triggering lazy queries.
        if ($actor instanceof Officer || $actor instanceof Manager) {
            $actor->loadMissing(['districts', 'hub']);
        } elseif ($actor instanceof User) {
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

        return response()->json([
            'actor_type' => $type->value,
            'profile' => $actor instanceof Officer
                ? $this->buildOfficerAuthProfile->build($actor, $request)
                : (new AuthProfileResource($actor))->toArray($request),
        ]);
    }
}
