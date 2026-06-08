<?php

namespace App\Http\Controllers;

use App\Http\Requests\Districts\DeleteDistrictRequest;
use App\Http\Requests\Districts\StoreDistrictRequest;
use App\Http\Requests\Districts\UpdateDistrictRequest;
use App\Http\Resources\DistrictResource;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DistrictController extends Controller
{
    /**
     * List districts ordered by name, each with assignment/reference counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $districts = District::query()
            ->with('hub')
            ->withCount(['managers', 'officers', 'issues'])
            ->orderBy('name')
            ->get();

        return DistrictResource::collection($districts);
    }

    /**
     * Show a single district with assignment/reference counts.
     */
    public function show(District $district): DistrictResource
    {
        $district->load('hub')->loadCount(['managers', 'officers', 'issues']);

        return new DistrictResource($district);
    }

    /**
     * Create a district.
     *
     * Authorization (active main manager only) and validation are enforced by
     * StoreDistrictRequest. Only validated district attributes are persisted;
     * actor district assignments remain managed through their dedicated pivot
     * endpoints.
     */
    public function store(StoreDistrictRequest $request): JsonResponse
    {
        $district = District::create($request->safe()->only([
            'hub_id',
            'name',
            'postal_prefix',
            'center_lat',
            'center_lng',
            'radius_meters',
            'is_active',
        ]));

        $district->load('hub')->loadCount(['managers', 'officers', 'issues']);

        return (new DistrictResource($district))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a district.
     *
     * Authorization (active main manager only) and validation are enforced by
     * UpdateDistrictRequest. Only provided validated keys are applied, so partial
     * updates leave untouched fields intact.
     */
    public function update(UpdateDistrictRequest $request, District $district): DistrictResource
    {
        $attributes = $request->safe()->only([
            'hub_id',
            'name',
            'postal_prefix',
            'center_lat',
            'center_lng',
            'radius_meters',
            'is_active',
        ]);

        if ($attributes !== []) {
            $district->update($attributes);
        }

        $district->refresh()->load('hub')->loadCount(['managers', 'officers', 'issues']);

        return new DistrictResource($district);
    }

    /**
     * Hard delete an eligible district.
     *
     * Authorization (active main manager only) is enforced by
     * DeleteDistrictRequest. Deletion is rejected when the district is still assigned to any manager,
     * assigned to any officer, or referenced by any issue. This keeps actor
     * assignments intact and preserves the singular `issues.district_id` issue
     * location/reference boundary.
     */
    public function destroy(DeleteDistrictRequest $request, District $district): JsonResponse
    {
        if ($district->managers()->exists()) {
            return response()->json([
                'message' => 'This district is still assigned to one or more managers and cannot be deleted. Reassign those managers first.',
            ], Response::HTTP_CONFLICT);
        }

        if ($district->officers()->exists()) {
            return response()->json([
                'message' => 'This district is still assigned to one or more officers and cannot be deleted. Reassign those officers first.',
            ], Response::HTTP_CONFLICT);
        }

        if ($district->issues()->exists()) {
            return response()->json([
                'message' => 'This district is still referenced by one or more issues and cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        $district->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
