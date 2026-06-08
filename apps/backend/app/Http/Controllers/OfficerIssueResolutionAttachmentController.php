<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\DownloadOfficerIssueResolutionAttachmentRequest;
use App\Models\Issue;
use App\Models\OfficerIssueResolutionAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficerIssueResolutionAttachmentController extends Controller
{
    private const DISK = 'local';

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
}
