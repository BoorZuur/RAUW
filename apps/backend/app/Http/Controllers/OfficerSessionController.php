<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficerSessions\IndexOfficerSessionRequest;
use App\Http\Resources\OfficerSessionResource;
use App\Models\OfficerSession;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Officers
 */
class OfficerSessionController extends Controller
{
    /**
     * List officer login sessions with pagination.
     *
     * Authorization is enforced by {@see IndexOfficerSessionRequest}, which
     * restricts this action to an authenticated, active manager. Optional
     * `officer_id`, `hub_id`, and `is_hub_active` filters narrow the result set.
     * Results are ordered newest-first by `shift_start` then `id`.
     */
    public function index(IndexOfficerSessionRequest $request): AnonymousResourceCollection
    {
        $hubActiveFilter = $request->wantsHubActiveSessions();

        $sessions = OfficerSession::query()
            ->when(
                $request->filled('officer_id'),
                fn ($query) => $query->where('officer_id', $request->integer('officer_id')),
            )
            ->when(
                $request->filled('hub_id'),
                fn ($query) => $query->where('hub_id', $request->integer('hub_id')),
            )
            ->when(
                $hubActiveFilter !== null,
                fn ($query) => $query->where('is_hub_active', $hubActiveFilter),
            )
            ->with(['officer', 'hub'])
            ->orderByDesc('shift_start')
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return OfficerSessionResource::collection($sessions);
    }
}
