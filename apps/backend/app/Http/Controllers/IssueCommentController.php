<?php

namespace App\Http\Controllers;

use App\Enums\ActorType;
use App\Http\Requests\IssueComments\DeleteCommentRequest;
use App\Http\Requests\IssueComments\IndexCommentRequest;
use App\Http\Requests\IssueComments\StoreCommentRequest;
use App\Http\Requests\IssueComments\UpdateCommentRequest;
use App\Http\Requests\IssueComments\UpdateCommentVisibilityRequest;
use App\Http\Resources\IssueCommentResource;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Officer;
use App\Models\User;
use App\Support\CommentVisibilityQuery;
use App\Support\IssueVisibilityQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class IssueCommentController extends Controller
{
    /**
     * List comments for a given issue.
     *
     * Validates that the issue is visible to the actor, and applies visibility-scoping
     * to the comment query builder. Eager-loads user/officer authors, and returns
     * comments sorted chronologically (oldest first).
     */
    public function index(IndexCommentRequest $request, Issue $issue): AnonymousResourceCollection
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $comments = CommentVisibilityQuery::applyVisibilityScope(
            IssueComment::query()->with(['user', 'officer']),
            $request->user(),
        )
            ->where('issue_id', $issue->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return IssueCommentResource::collection($comments);
    }

    /**
     * Create a comment on behalf of the authenticated active user or officer.
     *
     * Validates that the issue is visible to the actor before creating the comment.
     */
    public function store(StoreCommentRequest $request, Issue $issue): JsonResponse
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $actor = $request->user();

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'author_type' => $actor instanceof User ? ActorType::User : ActorType::Officer,
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'officer_id' => $actor instanceof Officer ? $actor->getKey() : null,
            'content' => $request->input('content'),
        ]);

        $comment->load(['user', 'officer']);

        return (new IssueCommentResource($comment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a comment.
     *
     * Ownership and validation are enforced in UpdateCommentRequest.
     */
    public function update(UpdateCommentRequest $request, Issue $issue, IssueComment $comment): IssueCommentResource
    {
        if ($comment->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        if (! CommentVisibilityQuery::canViewComment($comment, $request->user())) {
            abort(404);
        }

        $comment->update($request->safe()->only(['content']));

        $comment->refresh()->load(['user', 'officer']);

        return new IssueCommentResource($comment);
    }

    /**
     * Hard delete a comment.
     *
     * Ownership/manager permission is enforced in DeleteCommentRequest.
     */
    public function destroy(DeleteCommentRequest $request, Issue $issue, IssueComment $comment): JsonResponse
    {
        if ($comment->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        if (! CommentVisibilityQuery::canViewComment($comment, $request->user())) {
            abort(404);
        }

        $comment->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Set a comment's visibility.
     *
     * Authorization is enforced in UpdateCommentVisibilityRequest.
     */
    public function updateVisibility(UpdateCommentVisibilityRequest $request, Issue $issue, IssueComment $comment): IssueCommentResource
    {
        if ($comment->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        if (! CommentVisibilityQuery::canViewComment($comment, $request->user())) {
            abort(404);
        }

        $comment->update($request->safe()->only(['visibility']));

        $comment->refresh()->load(['user', 'officer']);

        return new IssueCommentResource($comment);
    }
}
