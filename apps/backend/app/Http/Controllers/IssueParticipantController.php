<?php

namespace App\Http\Controllers;

use App\Actions\Issues\JoinIssueAsParticipant;
use App\Actions\Issues\LeaveIssueParticipation;
use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\Issues\IndexIssueParticipantsRequest;
use App\Http\Requests\Issues\JoinIssueRequest;
use App\Http\Requests\Issues\LeaveIssueRequest;
use App\Http\Resources\IssueParticipantResource;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\User;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateConflict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     * List participants for a canonical issue.
     *
     * Authorization is enforced by IndexIssueParticipantsRequest, which restricts
     * this action to an authenticated, active officer or manager. Duplicate child
     * route ids resolve to the canonical parent. Issues not visible to the actor
     * return 404. Participants are ordered by joined_at ascending and paginated
     * with IssueParticipantResource payloads.
     */
    public function index(
        IndexIssueParticipantsRequest $request,
        Issue $issue,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): AnonymousResourceCollection {
        $canonical = $resolveCanonicalIssue->resolve($issue);

        if (! IssueVisibilityQuery::canViewIssue($canonical, $request->user())) {
            abort(404);
        }

        $participants = $canonical->participants()
            ->with(['user', 'viaIssue', 'issue'])
            ->orderBy('joined_at')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return IssueParticipantResource::collection($participants);
    }

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
