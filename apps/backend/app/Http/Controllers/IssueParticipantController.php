<?php

namespace App\Http\Controllers;

use App\Actions\Issues\JoinIssueAsParticipant;
use App\Actions\Issues\LeaveIssueParticipation;
use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\Issues\JoinIssueRequest;
use App\Http\Requests\Issues\LeaveIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\User;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateConflict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IssueParticipantController extends Controller
{
    /**
     * The relations eager loaded for every issue payload to avoid N+1 queries.
     *
     * @var array<int, string>
     */
    private const ISSUE_RELATIONS = [
        'user',
        'category',
        'district',
        'departments',
        'attachments',
        'officerResolution.officer',
        'officerResolution.attachments',
    ];

    /**
     * Join a canonical issue as a participant.
     *
     * Duplicate child ids are rejected with 422; repeat joins are idempotent (200).
     */
    public function join(
        JoinIssueRequest $request,
        Issue $issue,
        JoinIssueAsParticipant $joinIssueAsParticipant,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($issue->duplicate_of_id !== null) {
            throw IssueDuplicateConflict::cannotJoinDuplicateChild();
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $actor)) {
            abort(404);
        }

        $result = $joinIssueAsParticipant->join($actor, $issue, $request->boolean('is_anonymous'));
        $result['issue']->load(self::ISSUE_RELATIONS);

        return (new IssueResource($result['issue']))
            ->response()
            ->setStatusCode($result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    /**
     * Leave participation on the canonical issue (child route ids resolve to canonical).
     */
    public function leave(
        LeaveIssueRequest $request,
        Issue $issue,
        LeaveIssueParticipation $leaveIssueParticipation,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $canonical = $resolveCanonicalIssue->resolve($issue);

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        $leaveIssueParticipation->leave($actor, $canonical);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
