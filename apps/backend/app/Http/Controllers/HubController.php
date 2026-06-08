<?php

namespace App\Http\Controllers;

use App\Http\Requests\Hubs\DeleteHubRequest;
use App\Http\Requests\Hubs\IndexHubRequest;
use App\Http\Requests\Hubs\ShowHubRequest;
use App\Http\Requests\Hubs\StoreHubRequest;
use App\Http\Requests\Hubs\UpdateHubRequest;
use App\Http\Resources\HubResource;
use App\Models\Hub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HubController extends Controller
{
    /**
     * List hubs ordered by name, each with reference counts.
     */
    public function index(IndexHubRequest $request): AnonymousResourceCollection
    {
        $hubs = Hub::query()
            ->withCount(['districts', 'officers', 'managers'])
            ->orderBy('name')
            ->get();

        return HubResource::collection($hubs);
    }

    /**
     * Show a single hub with reference counts.
     */
    public function show(ShowHubRequest $request, Hub $hub): HubResource
    {
        $hub->loadCount(['districts', 'officers', 'managers']);

        return new HubResource($hub);
    }

    /**
     * Create a hub.
     *
     * Authorization (active main manager only) and validation are enforced by
     * StoreHubRequest.
     */
    public function store(StoreHubRequest $request): JsonResponse
    {
        $hub = Hub::create($request->safe()->only([
            'name',
            'address',
            'postal_code',
            'latitude',
            'longitude',
        ]));

        $hub->loadCount(['districts', 'officers', 'managers']);

        return (new HubResource($hub))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a hub.
     *
     * Authorization (active main manager only) and validation are enforced by
     * UpdateHubRequest.
     */
    public function update(UpdateHubRequest $request, Hub $hub): HubResource
    {
        $attributes = $request->safe()->only([
            'name',
            'address',
            'postal_code',
            'latitude',
            'longitude',
        ]);

        if ($attributes !== []) {
            $hub->update($attributes);
        }

        $hub->refresh()->loadCount(['districts', 'officers', 'managers']);

        return new HubResource($hub);
    }

    /**
     * Hard delete an eligible hub.
     *
     * Deletion is rejected when districts, officers, or managers still reference
     * the hub.
     */
    public function destroy(DeleteHubRequest $request, Hub $hub): JsonResponse
    {
        if ($hub->districts()->exists()) {
            return response()->json([
                'message' => 'This hub is still referenced by one or more districts and cannot be deleted. Reassign or delete those districts first.',
            ], Response::HTTP_CONFLICT);
        }

        if ($hub->officers()->exists()) {
            return response()->json([
                'message' => 'This hub is still assigned to one or more officers and cannot be deleted. Reassign those officers first.',
            ], Response::HTTP_CONFLICT);
        }

        if ($hub->managers()->exists()) {
            return response()->json([
                'message' => 'This hub is still assigned to one or more managers and cannot be deleted. Reassign those managers first.',
            ], Response::HTTP_CONFLICT);
        }

        $hub->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
