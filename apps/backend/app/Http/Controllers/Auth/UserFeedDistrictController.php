<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DistrictAssignments\UpdateUserFeedDistrictsRequest;
use App\Http\Resources\AuthProfileResource;
use Illuminate\Http\JsonResponse;

class UserFeedDistrictController extends Controller
{
    /**
     * Update the authenticated user's active feed districts.
     */
    public function update(UpdateUserFeedDistrictsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $user->feedDistricts()->sync($request->validated('district_ids'));

        $user->load('feedDistricts');

        return response()->json([
            'actor_type' => ActorType::User->value,
            'profile' => (new AuthProfileResource($user))->toArray($request),
        ]);
    }
}

