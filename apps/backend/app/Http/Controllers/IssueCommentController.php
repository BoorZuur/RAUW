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
use App\Models\Manager;
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
     * to the comment query builder. Eager-loads user/officer/manager authors and the
     * parent issue, and returns comments sorted chronologically (oldest first).
     */
    public function index(IndexCommentRequest $request, Issue $issue): AnonymousResourceCollection
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $comments = CommentVisibilityQuery::applyVisibilityScope(
            IssueComment::query()->with(['user', 'officer', 'manager', 'issue']),
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
     * Create a comment on behalf of the authenticated active user, officer, or manager.
     *
     * Validates that the issue is visible to the actor before creating the comment.
     */
    public function store(StoreCommentRequest $request, Issue $issue): JsonResponse
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $actor = $request->user();

        $attributes = [
            'issue_id' => $issue->id,
            'content' => $request->validated('content'),
            'user_id' => null,
            'officer_id' => null,
            'manager_id' => null,
        ];

        if ($actor instanceof User) {
            $attributes['author_type'] = ActorType::User;
            $attributes['user_id'] = $actor->getKey();
        } elseif ($actor instanceof Officer) {
            $attributes['author_type'] = ActorType::Officer;
            $attributes['officer_id'] = $actor->getKey();
        } elseif ($actor instanceof Manager) {
            $attributes['author_type'] = ActorType::Manager;
            $attributes['manager_id'] = $actor->getKey();
        }

        $comment = IssueComment::create($attributes);

        $comment->load(['user', 'officer', 'manager', 'issue']);

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

        $comment->refresh()->load(['user', 'officer', 'manager', 'issue']);

        return new IssueCommentResource($comment);
    }

    /**
     * Hard delete a comment.
     *
     * Ownership/manager permission is enforced in DeleteCommentRequest.
     */
    public function destroy(DeleteCommentRequest $request, Issue $issue, IssueComment $comment): Response
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

        return response()->noContent();
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

        $comment->refresh()->load(['user', 'officer', 'manager', 'issue']);

        return new IssueCommentResource($comment);
    }
}
