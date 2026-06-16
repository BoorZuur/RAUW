<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Http\Requests\Notifications\UpdateNotificationRequest;
use App\Http\Resources\DomainNotificationResource;
use App\Models\DomainNotification;
use App\Models\Officer;
use App\Support\Notifications\NotificationRecipientQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficerMeNotificationController extends Controller
{
    public function index(IndexNotificationRequest $request): AnonymousResourceCollection
    {
        /** @var Officer $officer */
        $officer = $request->user();

        $paginator = NotificationRecipientQuery::forOfficer($officer, $request->validated())
            ->paginate($request->perPage());

        return DomainNotificationResource::collection($paginator);
    }

    public function update(
        UpdateNotificationRequest $request,
        DomainNotification $notification,
    ): DomainNotificationResource {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! NotificationRecipientQuery::officerOwns($officer, $notification)) {
            abort(404, 'notification_not_found');
        }

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
            $notification->refresh();
        }

        return new DomainNotificationResource($notification);
    }
}
