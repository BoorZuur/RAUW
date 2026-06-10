<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityPosts\DeleteCommunityPostRequest;
use App\Http\Requests\CommunityPosts\IndexCommunityPostRequest;
use App\Http\Requests\CommunityPosts\StoreCommunityPostRequest;
use App\Http\Requests\CommunityPosts\UpdateCommunityPostRequest;
use App\Http\Requests\CommunityPosts\UpdateCommunityPostVisibilityRequest;
use App\Http\Resources\CommunityPostResource;
use App\Models\CommunityPost;
use App\Support\CommunityPostFeedQuery;
use App\Support\CommunityPostVisibilityQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CommunityPostController extends Controller
{
    public function __construct(
        private readonly CommunityPostFeedQuery $feedQuery,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexCommunityPostRequest $request)
    {
        $actor = $request->user();
        
        $query = $this->feedQuery->build($actor);

        if ($request->has('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }

        if ($request->boolean('saved_only') && $actor instanceof \App\Models\User) {
            $query->whereHas('savedByUsers', function ($q) use ($actor) {
                $q->where('user_id', $actor->id);
            });
        }

        $posts = $query
            ->with(['officer', 'district', 'attachments'])
            ->withCount('savedByUsers as saved_count')
            ->latest()
            ->paginate(15);

        // Append is_saved boolean for users
        if ($actor instanceof \App\Models\User) {
            $savedIds = $actor->savedCommunityPosts()
                ->whereIn('community_post_id', $posts->pluck('id'))
                ->pluck('community_post_id')
                ->toArray();

            $posts->getCollection()->transform(function ($post) use ($savedIds) {
                $post->is_saved = in_array($post->id, $savedIds, true);
                return $post;
            });
        }

        return CommunityPostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommunityPostRequest $request)
    {
        /** @var \App\Models\Officer $officer */
        $officer = $request->user();

        // Check if officer can post in this district (must be assigned)
        if (! $officer->districts()->where('districts.id', $request->input('district_id'))->exists()) {
            return response()->json([
                'message' => 'You are not assigned to this district.',
            ], Response::HTTP_FORBIDDEN);
        }

        $post = $officer->communityPosts()->create($request->validated());

        $post->load(['officer', 'district', 'attachments'])->loadCount('savedByUsers as saved_count');

        return (new CommunityPostResource($post))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(CommunityPost $communityPost)
    {
        if (! CommunityPostVisibilityQuery::canViewPost($communityPost, auth()->user())) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $communityPost->load(['officer', 'district', 'attachments'])->loadCount('savedByUsers as saved_count');

        if (auth()->user() instanceof \App\Models\User) {
            $communityPost->is_saved = auth()->user()->savedCommunityPosts()->where('community_post_id', $communityPost->id)->exists();
        }

        return new CommunityPostResource($communityPost);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommunityPostRequest $request, CommunityPost $communityPost)
    {
        if ($request->has('district_id')) {
            // Check if officer is assigned to new district
            /** @var \App\Models\Officer $officer */
            $officer = $request->user();
            if (! $officer->districts()->where('districts.id', $request->input('district_id'))->exists()) {
                return response()->json([
                    'message' => 'You are not assigned to this district.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $communityPost->update($request->validated());

        $communityPost->refresh()->load(['officer', 'district', 'attachments'])->loadCount('savedByUsers as saved_count');

        if ($request->user() instanceof \App\Models\User) {
            $communityPost->is_saved = $request->user()->savedCommunityPosts()->where('community_post_id', $communityPost->id)->exists();
        }

        return new CommunityPostResource($communityPost);
    }

    /**
     * Update the visibility of the specified resource.
     */
    public function updateVisibility(UpdateCommunityPostVisibilityRequest $request, CommunityPost $communityPost)
    {
        $communityPost->update($request->safe()->only('visibility'));

        if ($communityPost->visibility->value === \App\Enums\Visibility::Hidden->value) {
            $communityPost->savedByUsers()->detach();
        }

        $communityPost->refresh()->load(['officer', 'district', 'attachments'])->loadCount('savedByUsers as saved_count');

        if ($request->user() instanceof \App\Models\User) {
            $communityPost->is_saved = $request->user()->savedCommunityPosts()->where('community_post_id', $communityPost->id)->exists();
        }

        return new CommunityPostResource($communityPost);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteCommunityPostRequest $request, CommunityPost $communityPost)
    {
        $communityPost->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
