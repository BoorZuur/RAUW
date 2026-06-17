<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\BulkReadNotificationsRequest;
use App\Models\User;
use App\Support\Notifications\NotificationRecipientQuery;
use Illuminate\Http\JsonResponse;

class NotificationBulkReadController extends Controller
{
    public function update(BulkReadNotificationsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ids = $request->validated('ids');

        $updated = NotificationRecipientQuery::visibleForUser($user)
            ->whereIn('id', $ids)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }
}
