<?php

namespace App\Http\Controllers;

use App\Http\Requests\Managers\IndexMainManagerRequest;
use App\Http\Resources\ManagerResource;
use App\Models\Manager;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
            ->with(['departments', 'districts'])
            ->orderBy('username')
            ->paginate($request->perPage())
            ->withQueryString();

        return ManagerResource::collection($managers);
    }
}
