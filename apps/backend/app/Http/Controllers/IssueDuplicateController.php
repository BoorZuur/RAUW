<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\IndexIssueDuplicatesRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateAssertions;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueDuplicateController extends Controller
{
    /**
     * The relations eager loaded for every duplicate child payload to avoid N+1
     * queries.
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
     * List duplicate children for a canonical issue.
     *
     * Authorization is enforced by IndexIssueDuplicatesRequest, which restricts
     * this action to an authenticated, active officer or manager. The target
     * must be a canonical issue; duplicate child route ids return 422
     * issue_not_canonical. Issues not visible to the actor return 404.
     * Children are ordered oldest-first by created_at then id and paginated
     * with full IssueResource payloads (officers and managers are never
     * participant-redacted).
     */
    public function index(IndexIssueDuplicatesRequest $request, Issue $issue): AnonymousResourceCollection
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        IssueDuplicateAssertions::assertCanonical($issue);

        $duplicates = $issue->duplicates()
            ->with(self::ISSUE_RELATIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return IssueResource::collection($duplicates);
    }
}
