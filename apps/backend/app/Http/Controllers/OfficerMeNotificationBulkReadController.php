<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\BulkReadNotificationsRequest;
use App\Models\DomainNotification;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

class OfficerMeNotificationBulkReadController extends Controller
{
    public function update(BulkReadNotificationsRequest $request): JsonResponse
    {
        /** @var Officer $officer */
        $officer = $request->user();

        $ids = $request->validated('ids');

        $updated = DomainNotification::query()
            ->forOfficer($officer)
            ->whereIn('id', $ids)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }
}
