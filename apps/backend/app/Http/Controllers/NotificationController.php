<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Http\Requests\Notifications\UpdateNotificationRequest;
use App\Http\Resources\DomainNotificationResource;
use App\Models\DomainNotification;
use App\Models\User;
use App\Support\Notifications\NotificationRecipientQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $paginator = NotificationRecipientQuery::forUser($user, $request->validated())
            ->paginate($request->perPage());

        return DomainNotificationResource::collection($paginator);
    }

    public function update(
        UpdateNotificationRequest $request,
        DomainNotification $notification,
    ): DomainNotificationResource {
        /** @var User $user */
        $user = $request->user();

        if (! NotificationRecipientQuery::userOwns($user, $notification)) {
            abort(404, 'notification_not_found');
        }

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
            $notification->refresh();
        }

        return new DomainNotificationResource($notification);
    }
}
