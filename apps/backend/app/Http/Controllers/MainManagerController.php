<?php

namespace App\Http\Controllers;

use App\Http\Requests\Managers\DisableMainManagerRequest;
use App\Http\Requests\Managers\EnableMainManagerRequest;
use App\Http\Requests\Managers\IndexMainManagerRequest;
use App\Http\Requests\Managers\ShowMainManagerRequest;
use App\Http\Requests\Managers\UpdateMainManagerRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use App\Models\Officer;
use App\Support\OfficerHubScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MainManagerController extends Controller
{
    /**
     * List main managers with pagination.
     *
     * Authorization is enforced by IndexMainManagerRequest, which restricts
     * this action to an authenticated, active, main manager. Results include
     * only managers with `is_main_manager = true`, eager-loaded departments
     * and districts, ordered by username ascending. Pagination is bounded so
     * `per_page` can never exceed a safe maximum.
     */
    public function index(IndexMainManagerRequest $request): AnonymousResourceCollection
    {
        $managers = Manager::query()
            ->where('is_main_manager', true)
            ->with(['departments', 'districts', 'hub'])
            ->orderBy('username')
            ->paginate($request->perPage())
            ->withQueryString();

        return ManagerResource::collection($managers);
    }

    /**
     * Show a single main manager profile.
     *
     * Authorization is enforced by ShowMainManagerRequest, which restricts this
     * action to an authenticated, active officer or manager. Route binding
     * limits the target to rows with `is_main_manager = true`. Hub scoping
     * returns 404 when the actor cannot view the manager in the same hub,
     * including for main managers (no city-wide bypass).
     */
    public function show(ShowMainManagerRequest $request, Manager $manager): ManagerResource
    {
        /** @var Officer|Manager $actor */
        $actor = $request->user();

        if (! OfficerHubScope::actorCanViewInHub($actor, $manager)) {
            abort(404);
        }

        $manager->load(['departments', 'districts', 'hub']);

        return new ManagerResource($manager);
    }

    /**
     * Update another main manager's identity fields.
     *
     * Authorization is enforced by UpdateMainManagerRequest, which restricts
     * this action to an authenticated, active, main manager. Only username,
     * email, and password may be changed; privileged fields are rejected with
     * validation errors. The route binding limits the target to rows with
     * `is_main_manager = true`.
     */
    public function update(UpdateMainManagerRequest $request, Manager $manager): ManagerResource
    {
        $attributes = $request->changedAttributes();

        if ($attributes !== []) {
            $manager->fill($attributes);
            $manager->save();
        }

        $manager->load(['departments', 'districts', 'hub']);

        return new ManagerResource($manager);
    }

    /**
     * Disable a main manager without removing the row.
     *
     * Authorization is enforced by DisableMainManagerRequest. Setting
     * `is_active = false` preserves the manager record and historical
     * associations. Deactivation is blocked when the target is the last active
     * main manager so the system always retains at least one.
     */
    public function disable(DisableMainManagerRequest $request, Manager $manager): ManagerResource|JsonResponse
    {
        if ($this->isLastActiveMainManager($manager)) {
            return response()->json([
                'message' => 'Cannot deactivate the last active main manager.',
            ], Response::HTTP_CONFLICT);
        }

        $manager->update(['is_active' => false]);

        $manager->load(['departments', 'districts', 'hub']);

        return new ManagerResource($manager);
    }

    /**
     * Enable a main manager without restoring a soft-deleted row.
     *
     * Authorization is enforced by EnableMainManagerRequest. Setting
     * `is_active = true` reactivates the main manager. Re-enabling an already
     * active main manager is idempotent and returns 200. Unlike disable, no
     * last-active-main-manager guard applies.
     */
    public function enable(EnableMainManagerRequest $request, Manager $manager): ManagerResource
    {
        $manager->update(['is_active' => true]);

        $manager->load(['departments', 'districts', 'hub']);

        return new ManagerResource($manager);
    }

    /**
     * Whether disabling the given manager would leave no active main manager.
     */
    private function isLastActiveMainManager(Manager $manager): bool
    {
        if (! $manager->is_active) {
            return false;
        }

        return Manager::query()
            ->where('is_main_manager', true)
            ->where('is_active', true)
            ->count() <= 1;
    }
}
