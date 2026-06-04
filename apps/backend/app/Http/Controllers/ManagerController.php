<?php

namespace App\Http\Controllers;

use App\Http\Requests\Managers\DisableManagerRequest;
use App\Http\Requests\Managers\IndexManagerRequest;
use App\Http\Requests\Managers\StoreManagerRequest;
use App\Http\Requests\Managers\UpdateManagerRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ManagerController extends Controller
{
    /**
     * List ordinary managers with pagination.
     *
     * Authorization is enforced by IndexManagerRequest, which restricts
     * this action to an authenticated, active, main manager. Results include
     * only managers with `is_main_manager = false`, eager-loaded departments
     * and districts, ordered by username ascending. Pagination is bounded so
     * `per_page` can never exceed a safe maximum.
     */
    public function index(IndexManagerRequest $request): AnonymousResourceCollection
    {
        $managers = Manager::query()
            ->where('is_main_manager', false)
            ->with(['departments', 'districts'])
            ->orderBy('username')
            ->paginate($request->perPage())
            ->withQueryString();

        return ManagerResource::collection($managers);
    }

    /**
     * Create a new manager on behalf of the authenticated main manager.
     *
     * Authorization is enforced by StoreManagerRequest, which restricts this
     * action to an authenticated, active, main manager. The created manager is
     * an ordinary active manager (never a main manager) linked back to the
     * creator through `created_by_manager_id`. No Sanctum token is issued for
     * the new manager: they must authenticate separately via the shared login
     * endpoint. The response excludes the password hash, remember token, and
     * any access tokens.
     */
    public function store(StoreManagerRequest $request): JsonResponse
    {
        /** @var Manager $creator */
        $creator = $request->user();

        $manager = Manager::create([
            'username' => $request->username(),
            'email' => $request->email(),
            'password' => $request->password(),
            'created_by_manager_id' => $creator->id,
        ]);

        // Persist the manager's one-or-more department assignments in the pivot.
        $manager->departments()->sync($request->departmentIds());

        // Persist any optional district assignments through the pivot. Managers
        // may be created with no district assignments at all.
        $manager->districts()->sync($request->districtIds());

        // `is_active = true` and `is_main_manager = false` are applied by the
        // model's attribute defaults; no client input can override them.

        $manager->loadMissing('departments', 'districts');

        return (new ManagerResource($manager))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update another ordinary manager's identity fields.
     *
     * Authorization is enforced by UpdateManagerRequest, which restricts
     * this action to an authenticated, active, main manager. Only username,
     * email, and password may be changed; privileged fields are rejected with
     * validation errors. The route binding limits the target to rows with
     * `is_main_manager = false`.
     */
    public function update(UpdateManagerRequest $request, Manager $manager): ManagerResource
    {
        $attributes = $request->changedAttributes();

        if ($attributes !== []) {
            $manager->fill($attributes);
            $manager->save();
        }

        $manager->load(['departments', 'districts']);

        return new ManagerResource($manager);
    }

    /**
     * Disable an ordinary manager without removing the row.
     *
     * Authorization is enforced by DisableManagerRequest. Setting
     * `is_active = false` preserves the manager record and historical
     * associations.
     */
    public function disable(DisableManagerRequest $request, Manager $manager): ManagerResource
    {
        $manager->update(['is_active' => false]);

        $manager->load(['departments', 'districts']);

        return new ManagerResource($manager);
    }
}
