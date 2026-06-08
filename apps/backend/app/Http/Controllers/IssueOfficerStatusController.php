<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Http\Requests\Issues\UpdateIssueStatusRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\IssueStatusHistory;
use App\Models\Officer;
use App\Support\IssueStatusTransition;
use App\Support\IssueVisibilityQuery;
use Illuminate\Support\Facades\DB;

class IssueOfficerStatusController extends Controller
{
    /**
     * The relations eager loaded for every issue payload to avoid N+1 queries.
     *
     * @var array<int, string>
     */
    private const ISSUE_RELATIONS = ['user', 'category', 'district', 'departments', 'attachments'];

    /**
     * Update an assigned issue's status along the directed officer workflow.
     *
     * Authorization, district access, assignee checks, and transition validation
     * are enforced by UpdateIssueStatusRequest. Visibility scope returns 404
     * when the issue is not viewable. Each successful change appends one
     * status history row without coordinates and may set resolved_at on the
     * first transition to opgelost.
     */
    public function update(UpdateIssueStatusRequest $request, Issue $issue): IssueResource
    {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        /** @var IssueStatus $newStatus */
        $newStatus = $request->enum('status', IssueStatus::class);
        $note = $request->validated('note');

        DB::transaction(function () use ($officer, $issue, $newStatus, $note): void {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $lockedIssue->status;

            $lockedIssue->update([
                'status' => $newStatus,
                'resolved_at' => IssueStatusTransition::resolvedAtForTransition(
                    $oldStatus,
                    $newStatus,
                    $lockedIssue->resolved_at,
                ),
            ]);

            IssueStatusHistory::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'changed_by_officer_id' => $officer->getKey(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
                'changed_at' => now(),
            ]);
        });

        $issue->refresh()->load(self::ISSUE_RELATIONS);

        return new IssueResource($issue);
    }
}
