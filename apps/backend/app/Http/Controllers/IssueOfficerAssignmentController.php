<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CloseAllOpenIssueChats;
use App\Enums\IssueStatus;
use App\Http\Requests\Issues\AssignIssueToOfficerRequest;
use App\Http\Requests\Issues\UnassignIssueFromOfficerRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\IssueStatusHistory;
use App\Models\Officer;
use App\Support\IssueVisibilityQuery;
use App\Support\OfficerIssueConflict;
use App\Support\OfficerIssueDistrictAccess;
use App\Support\OfficerIssueRowLock;

/**
 * @group Officer Actions on Issues
 */
class IssueOfficerAssignmentController extends Controller
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
     * Self-assign the authenticated officer to an issue in their district.
     *
     * Resolved (opgelost) and closed (gesloten) issues cannot be self-assigned (422
     * issue_not_assignable). Open issues also transition to in_behandeling with one
     * status history row. Already assigned to the requesting officer is idempotent.
     * Assignment to a different officer returns 409 without takeover.
     */
    public function store(AssignIssueToOfficerRequest $request, Issue $issue): IssueResource
    {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        if ($issue->assigned_officer_id === $officer->getKey()) {
            $issue->load(self::ISSUE_RELATIONS);

            return new IssueResource($issue);
        }

        OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use ($officer): void {
            OfficerIssueRowLock::assertAssignable($lockedIssue);
            OfficerIssueRowLock::assertUnassignedOrSelf($officer, $lockedIssue);

            if ($lockedIssue->assigned_officer_id === $officer->getKey()) {
                return;
            }

            $updates = ['assigned_officer_id' => $officer->getKey()];

            if ($lockedIssue->status === IssueStatus::Open) {
                $updates['status'] = IssueStatus::InProgress;

                IssueStatusHistory::query()->create([
                    'issue_id' => $lockedIssue->getKey(),
                    'changed_by_officer_id' => $officer->getKey(),
                    'old_status' => IssueStatus::Open,
                    'new_status' => IssueStatus::InProgress,
                    'note' => null,
                    'changed_at' => now(),
                ]);
            }

            $lockedIssue->update($updates);

            \App\Models\IssueOfficerAssignmentHistory::query()->firstOrCreate([
                'issue_id' => $lockedIssue->getKey(),
                'officer_id' => $officer->getKey(),
            ], [
                'assigned_at' => now(),
            ]);
        });

        $issue->refresh()->load(self::ISSUE_RELATIONS);

        return new IssueResource($issue);
    }

    /**
     * Unassign the authenticated officer from an issue they currently own.
     *
     * Status is unchanged; only assigned_officer_id is cleared. Already
     * unassigned issues are idempotent. Non-assignees receive 403.
     */
    public function destroy(
        UnassignIssueFromOfficerRequest $request,
        Issue $issue,
        CloseAllOpenIssueChats $closeAllOpenIssueChats,
    ): IssueResource {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        if ($issue->assigned_officer_id === null) {
            $issue->load(self::ISSUE_RELATIONS);

            return new IssueResource($issue);
        }

        OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use ($officer, $closeAllOpenIssueChats): void {
            if ($lockedIssue->assigned_officer_id === null) {
                return;
            }

            if ($lockedIssue->assigned_officer_id !== $officer->getKey()) {
                throw OfficerIssueConflict::notAssignedOfficer(
                    'Only the assigned officer may unassign from this issue.',
                );
            }

            $closeAllOpenIssueChats->closeAll($lockedIssue, $officer, withSystemMessage: false);

            $lockedIssue->update(['assigned_officer_id' => null]);
        });

        $issue->refresh()->load(self::ISSUE_RELATIONS);

        return new IssueResource($issue);
    }
}
