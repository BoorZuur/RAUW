<?php

namespace App\Http\Controllers;

use App\Actions\Issues\MarkIssueAsDuplicate;
use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\Issues\IndexIssueDuplicatesRequest;
use App\Http\Requests\Issues\MarkIssueAsDuplicateRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\Officer;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateAssertions;
use App\Support\Issues\IssueDuplicateConflict;
use App\Support\OfficerIssueDistrictAccess;
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

    /**
     * Link an existing issue to a canonical duplicate target.
     *
     * Officers must be assigned to both the child and canonical districts
     * (403 officer_not_in_district). Managers rely on IssueVisibilityQuery
     * only. Different owners re-parent the child as a hidden duplicate; same
     * owner merges participants onto the canonical and hard-deletes the child.
     * Tier C: hub-active session required for officers.
     */
    public function store(
        MarkIssueAsDuplicateRequest $request,
        Issue $issue,
        MarkIssueAsDuplicate $markIssueAsDuplicate,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): IssueResource {
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $actor)) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $issue);
        }

        $duplicateOfId = (int) $request->validated('duplicate_of_id');

        $target = Issue::query()->find($duplicateOfId);

        if ($target === null || ! IssueVisibilityQuery::canViewIssue($target, $actor)) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        $canonical = $resolveCanonicalIssue->resolve($target);

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        $result = $markIssueAsDuplicate->mark($actor, $issue, $duplicateOfId);

        return new IssueResource($result->load(self::ISSUE_RELATIONS));
    }
}
