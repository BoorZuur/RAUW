<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Models\DomainNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class NotificationUnreadCountController extends Controller
{
    public function show(IndexNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = DomainNotification::query()
            ->forUser($user)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
