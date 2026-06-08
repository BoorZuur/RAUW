<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\DownloadOfficerIssueResolutionAttachmentRequest;
use App\Models\Issue;
use App\Models\OfficerIssueResolutionAttachment;
use App\Support\IssueVisibilityQuery;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficerIssueResolutionAttachmentController extends Controller
{
    private const DISK = 'local';

    /**
     * Stream a resolution attachment file back to an authorized actor.
     */
    public function download(
        DownloadOfficerIssueResolutionAttachmentRequest $request,
        Issue $issue,
        OfficerIssueResolutionAttachment $attachment,
    ): StreamedResponse {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

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
