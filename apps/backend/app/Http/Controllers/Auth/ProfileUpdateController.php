<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildOfficerAuthProfile;
use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\AuthProfileResource;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 */
class ProfileUpdateController extends Controller
{
    public function __construct(
        private readonly BuildOfficerAuthProfile $buildOfficerAuthProfile,
    ) {
    }

    /**
     * Update the authenticated actor's own identity fields and return the
     * refreshed canonical auth profile payload.
     *
     * Authorization is enforced by {@see UpdateProfileRequest}, which restricts
     * this action to an authenticated user, officer, or manager. Active actors
     * may change username, email, password, and (for officers) badge_number;
     * inactive actors may only change username and password for recovery.
     * Department, district, and privileged fields are rejected with validation
     * errors. The response reuses the same {@see AuthProfileResource} shape as
     * `GET /api/auth/me`, with `actor_type` on the wrapper.
     */
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User|Officer|Manager $actor */
        $actor = $request->user();

        $attributes = $request->changedAttributes();

        if ($attributes !== []) {
            $actor->fill($attributes);
            $actor->save();
        }

        if ($actor instanceof Officer || $actor instanceof Manager) {
            $actor->load('departments', 'districts', 'hub');
        }

        $type = match (true) {
            $actor instanceof User => ActorType::User,
            $actor instanceof Officer => ActorType::Officer,
            $actor instanceof Manager => ActorType::Manager,
        };

        return response()->json([
            'actor_type' => $type->value,
            'profile' => $actor instanceof Officer
                ? $this->buildOfficerAuthProfile->build($actor, $request)
                : (new AuthProfileResource($actor))->toArray($request),
        ]);
    }
}
