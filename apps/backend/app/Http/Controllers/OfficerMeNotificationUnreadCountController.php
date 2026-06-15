<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Models\DomainNotification;
use App\Models\Officer;
use Illuminate\Http\JsonResponse;

class OfficerMeNotificationUnreadCountController extends Controller
{
    public function show(IndexNotificationRequest $request): JsonResponse
    {
        /** @var Officer $officer */
        $officer = $request->user();

        $count = DomainNotification::query()
            ->forOfficer($officer)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
