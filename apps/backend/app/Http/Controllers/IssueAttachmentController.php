<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\DeleteIssueAttachmentRequest;
use App\Http\Requests\Issues\DownloadIssueAttachmentRequest;
use App\Http\Requests\Issues\StoreIssueAttachmentRequest;
use App\Http\Resources\IssueAttachmentResource;
use App\Support\UploadedFileValidator;
use App\Models\Issue;
use App\Models\IssueAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueAttachmentController extends Controller
{
    /**
     * The non-public storage disk used for attachment files in development.
     *
     * The `local` disk roots at storage/app/private, so uploaded files are kept
     * outside the public web root and are only ever served through the
     * authenticated download endpoint below.
     */
    private const DISK = 'local';

    /**
     * The directory (relative to the disk root) where attachment files live.
     */
    private const DIRECTORY = 'issue-attachments';

    /**
     * Store one or more uploaded files against an owner's issue.
     *
     * Ownership, the per-file size/MIME constraints, the per-request file count,
     * and the cumulative cap against already-stored attachments are all enforced
     * by StoreIssueAttachmentRequest. Each validated file is written to the
     * non-public `local` disk under a per-issue directory using a hashed storage
     * name, and one IssueAttachment row is persisted per file capturing the
     * storage path, original filename, MIME type, byte size, and upload time.
     * The newly created attachments are returned as a `data`-wrapped collection
     * whose URLs point at the authenticated download endpoint, never a public
     * storage URL. The wrapping is applied explicitly here because the resource
     * itself disables wrapping (`$wrap = null`) for the flat single-resource
     * shape used elsewhere, while this upload endpoint contractually returns
     * `{ "data": [ ... ] }`.
     */
    public function store(StoreIssueAttachmentRequest $request, Issue $issue): JsonResponse
    {
        $files = $request->file('files', []);

        $created = [];

        foreach ($files as $file) {
            $path = $file->store(self::DIRECTORY.'/'.$issue->getKey(), self::DISK);

            $created[] = $issue->attachments()->create([
                'file_path' => $path,
                'file_url' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_type' => UploadedFileValidator::detectMimeType($file),
                'file_size' => $file->getSize(),
                'uploaded_at' => Carbon::now(),
            ]);
        }

        return response()->json(
            ['data' => IssueAttachmentResource::collection($created)],
            Response::HTTP_CREATED,
        );
    }

    /**
     * Stream an attachment file back to an authorized actor.
     *
     * Download authorization is enforced by DownloadIssueAttachmentRequest
     * via IssueVisibilityQuery (Q8 / D15-A visibility-only): users who may view the issue,
     * and any active officer or manager. Hidden issues follow existing visibility rules
     * (404 when not viewable). The attachment must belong to the issue named in the route —
     * a mismatch yields 404 so attachment ids cannot be probed across issues — and the
     * backing file must still exist on the non-public `local` disk. The file is returned
     * as a streamed download under its original client filename rather than its hashed
     * storage name.
     */
    public function download(DownloadIssueAttachmentRequest $request, Issue $issue, IssueAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->issue_id === $issue->getKey(), Response::HTTP_NOT_FOUND);

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($attachment->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }

    /**
     * Delete a single attachment from an owner's issue.
     *
     * Owner-only authorization is enforced by DeleteIssueAttachmentRequest, which
     * restricts the action to the authenticated, active regular user who owns the
     * route issue. The attachment must belong to the issue named in the route — a
     * mismatch yields a 404 so attachment ids cannot be probed or removed across
     * issues. The backing file is removed from the non-public `local` disk (a
     * missing file is tolerated so a partial prior cleanup cannot block deletion)
     * and the attachment row is deleted, returning an empty 204 response.
     */
    public function destroy(DeleteIssueAttachmentRequest $request, Issue $issue, IssueAttachment $attachment): Response
    {
        abort_unless($attachment->issue_id === $issue->getKey(), Response::HTTP_NOT_FOUND);

        $disk = Storage::disk(self::DISK);

        if ($disk->exists($attachment->file_path)) {
            $disk->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->noContent();
    }
}
