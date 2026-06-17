<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Models\User;
use App\Support\Notifications\NotificationRecipientQuery;
use Illuminate\Http\JsonResponse;

class NotificationUnreadCountController extends Controller
{
    public function show(IndexNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = NotificationRecipientQuery::visibleForUser($user)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
