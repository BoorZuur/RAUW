<?php

namespace App\Http\Controllers;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\Issues\IndexIssueStatusHistoryRequest;
use App\Http\Resources\IssueStatusHistoryResource;
use App\Models\Issue;
use App\Support\IssueVisibilityQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueStatusHistoryController extends Controller
{
    /**
     * List status history for a canonical issue.
     *
     * Authorization is enforced by IndexIssueStatusHistoryRequest, which
     * restricts this action to an authenticated, active officer or manager.
     * Duplicate child route ids resolve to the canonical parent. Issues not
     * visible to the actor return 404. Rows are ordered newest-first by
     * changed_at and paginated with IssueStatusHistoryResource payloads.
     */
    public function index(
        IndexIssueStatusHistoryRequest $request,
        Issue $issue,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): AnonymousResourceCollection {
        $canonical = $resolveCanonicalIssue->resolve($issue);

        if (! IssueVisibilityQuery::canViewIssue($canonical, $request->user())) {
            abort(404);
        }

        $history = $canonical->statusHistory()
            ->with('changedByOfficer')
            ->orderByDesc('changed_at')
            ->paginate($request->perPage())
            ->withQueryString();

        return IssueStatusHistoryResource::collection($history);
    }
}
