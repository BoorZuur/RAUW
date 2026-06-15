<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\MarkAllNotificationsReadRequest;
use App\Models\DomainNotification;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

class OfficerMeNotificationMarkAllReadController extends Controller
{
    public function store(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        /** @var Officer $officer */
        $officer = $request->user();

        $updated = DomainNotification::query()
            ->forOfficer($officer)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }
}
