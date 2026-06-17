<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ShowUserSettingsRequest;
use App\Http\Requests\User\UpdateUserSettingsRequest;
use App\Http\Resources\UserSettingsResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserSettingsController extends Controller
{
    public function show(ShowUserSettingsRequest $request): UserSettingsResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserSettingsResource($user);
    }

    public function update(UpdateUserSettingsRequest $request): UserSettingsResource
    {
        /** @var User $user */
        $user = $request->user();

        $user->update($request->validated());
        $user->refresh();

        return new UserSettingsResource($user);
    }
}
