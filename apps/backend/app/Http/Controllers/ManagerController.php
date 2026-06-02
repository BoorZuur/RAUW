<?php

namespace App\Http\Controllers;

use App\Http\Requests\Managers\StoreManagerRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ManagerController extends Controller
{
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
            'department' => $request->department(),
            'district_id' => $request->districtId(),
            'created_by_manager_id' => $creator->id,
        ]);

        // `is_active = true` and `is_main_manager = false` are applied by the
        // model's attribute defaults; no client input can override them.

        $manager->loadMissing('district');

        return (new ManagerResource($manager))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
