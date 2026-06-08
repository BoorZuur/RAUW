<?php

namespace App\Support;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Officer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficerIssueRowLock
{
    /**
     * Run a callback inside a transaction with the issue row locked for update.
     *
     * @template T
     *
     * @param  callable(Issue): T  $callback
     * @return T
     */
    public static function withLockedIssue(Issue $issue, callable $callback): mixed
    {
        return DB::transaction(function () use ($issue, $callback): mixed {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $callback($lockedIssue);
        });
    }

    /**
     * Assert the officer is the current assignee or throw 403.
     */
    public static function assertAssignee(
        Officer $officer,
        Issue $locked,
        ?string $message = null,
    ): void {
        if ($locked->assigned_officer_id !== $officer->getKey()) {
            throw OfficerIssueConflict::notAssignedOfficer(
                $message ?? 'Only the assigned officer may update this issue status.',
            );
        }
    }

    /**
     * Assert the issue status allows self-assign or throw 422.
     */
    public static function assertAssignable(Issue $locked): void
    {
        if (! $locked->status->isAssignable()) {
            throw OfficerIssueConflict::issueNotAssignable();
        }
    }

    /**
     * Assert the issue status allows resolution writes or throw 422.
     */
    public static function assertResolutionWritable(Issue $locked): void
    {
        if (! $locked->status->isResolutionWritable()) {
            throw OfficerIssueConflict::issueClosed();
        }
    }

    /**
     * Assert the issue is unassigned or already assigned to this officer.
     *
     * Idempotent when assigned to self; throws 409 when assigned to another officer.
     */
    public static function assertUnassignedOrSelf(Officer $officer, Issue $locked): void
    {
        if ($locked->assigned_officer_id === $officer->getKey()) {
            return;
        }

        if ($locked->assigned_officer_id !== null) {
            throw OfficerIssueConflict::issueAlreadyAssigned();
        }
    }

    /**
     * Assert no officer resolution exists for the issue or throw 409.
     */
    public static function assertNoResolution(Issue $locked): void
    {
        if ($locked->officerResolution()->exists()) {
            throw OfficerIssueConflict::officerResolutionExists();
        }
    }

    /**
     * Assert a valid status transition on the locked issue or throw 422.
     *
     * @throws ValidationException
     */
    public static function assertStatusTransition(Issue $locked, IssueStatus $to): void
    {
        $from = $locked->status;

        if ($from === $to) {
            throw ValidationException::withMessages([
                'status' => [
                    sprintf('Issue is already in status %s.', $to->value),
                ],
            ]);
        }

        IssueStatusTransition::assertTransition($from, $to);
    }
}
