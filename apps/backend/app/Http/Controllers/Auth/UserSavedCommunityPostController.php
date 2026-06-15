<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Support\CommunityPostVisibilityQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserSavedCommunityPostController extends Controller
{
    public function __construct(
        private readonly CommunityPostVisibilityQuery $visibilityQuery,
    ) {
    }

    /**
     * Save a community post to the user's saved list.
     */
    public function store(Request $request, CommunityPost $communityPost): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Ensure user can see the post before saving
        if (! CommunityPostVisibilityQuery::canViewPost($communityPost, $user)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $user->savedCommunityPosts()->syncWithoutDetaching([$communityPost->id]);

        return response()->json([], Response::HTTP_CREATED);
    }

    /**
     * Remove a community post from the user's saved list.
     */
    public function destroy(Request $request, CommunityPost $communityPost): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $user->savedCommunityPosts()->detach($communityPost->id);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
