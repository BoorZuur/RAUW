<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\MarkAllNotificationsReadRequest;
use App\Models\User;
use App\Support\Notifications\NotificationRecipientQuery;
use Illuminate\Http\JsonResponse;

class NotificationMarkAllReadController extends Controller
{
    public function store(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updated = NotificationRecipientQuery::visibleForUser($user)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }
}
