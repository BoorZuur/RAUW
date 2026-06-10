<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\DownloadOfficerIssueUpdateAttachmentRequest;
use App\Models\Issue;
use App\Models\OfficerIssueUpdateAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficerIssueUpdateAttachmentController extends Controller
{
    private const DISK = 'local';

    /**
     * Stream an update attachment file back to an authorized actor.
     */
    public function download(
        DownloadOfficerIssueUpdateAttachmentRequest $request,
        Issue $issue,
        OfficerIssueUpdateAttachment $attachment,
    ): StreamedResponse {
        $update = $attachment->officerIssueUpdate;

        if ($update === null
            || $update->issue_id !== $issue->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($attachment->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }
}
