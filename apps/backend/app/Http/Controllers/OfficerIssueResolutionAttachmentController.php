<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\DeleteOfficerIssueResolutionAttachmentRequest;
use App\Http\Requests\Issues\DownloadOfficerIssueResolutionAttachmentRequest;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\OfficerIssueResolutionAttachment;
use App\Support\IssueVisibilityQuery;
use App\Support\OfficerIssueDistrictAccess;
use App\Support\OfficerIssueRowLock;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Officer Actions on Issues
 */
class OfficerIssueResolutionAttachmentController extends Controller
{
    private const DISK = 'local';

    private const RESOLUTION_ASSIGNEE_MESSAGE = 'Only the assigned officer may submit or update the resolution for this issue.';

    /**
     * Stream a resolution attachment file back to an authorized actor.
     *
     * Download authorization is enforced by DownloadOfficerIssueResolutionAttachmentRequest
     * via IssueVisibilityQuery (Q8 / D15-A visibility-only): users who may view the issue,
     * and any active officer or manager. Hidden issues follow existing visibility rules
     * (404 when not viewable). The attachment must belong to the issue's resolution —
     * a mismatch yields 404 so attachment ids cannot be probed across issues — and the
     * backing file must still exist on the non-public `local` disk.
     */
    public function download(
        DownloadOfficerIssueResolutionAttachmentRequest $request,
        Issue $issue,
        OfficerIssueResolutionAttachment $attachment,
    ): StreamedResponse {
        $resolution = $issue->officerResolution;

        if ($resolution === null
            || $attachment->officer_issue_resolution_id !== $resolution->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($attachment->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }

    /**
     * Delete a single attachment from an officer resolution.
     *
     * Assignee-only authorization is enforced on the locked issue row via
     * OfficerIssueRowLock::assertAssignee. The attachment must belong to the
     * issue's resolution — a mismatch yields 404 so attachment ids cannot be
     * probed or removed across issues. The backing file is removed from the
     * non-public `local` disk (a missing file is tolerated so a partial prior
     * cleanup cannot block deletion) and the attachment row is deleted,
     * returning an empty 204 response. Repeat delete yields 404 once the row
     * is gone (route model binding).
     */
    public function destroy(
        DeleteOfficerIssueResolutionAttachmentRequest $request,
        Issue $issue,
        OfficerIssueResolutionAttachment $attachment,
    ): Response {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        $pathToDelete = null;

        OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use (
            $officer,
            $attachment,
            &$pathToDelete,
        ): void {
            OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, self::RESOLUTION_ASSIGNEE_MESSAGE);
            OfficerIssueRowLock::assertResolutionWritable($lockedIssue);

            $resolution = $lockedIssue->officerResolution;

            if ($resolution === null) {
                abort(Response::HTTP_NOT_FOUND);
            }

            $ownedAttachment = $resolution->attachments()
                ->whereKey($attachment->getKey())
                ->first();

            if ($ownedAttachment === null) {
                abort(Response::HTTP_NOT_FOUND);
            }

            $pathToDelete = $ownedAttachment->file_path;

            $ownedAttachment->delete();
        });

        $disk = Storage::disk(self::DISK);

        if ($pathToDelete !== null && $disk->exists($pathToDelete)) {
            $disk->delete($pathToDelete);
        }

        return response()->noContent();
    }
}
