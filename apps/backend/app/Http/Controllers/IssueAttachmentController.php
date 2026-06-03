<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\StoreIssueAttachmentRequest;
use App\Http\Resources\IssueAttachmentResource;
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
     * The newly created attachments are returned as a collection whose URLs point
     * at the authenticated download endpoint, never a public storage URL.
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
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_at' => Carbon::now(),
            ]);
        }

        return IssueAttachmentResource::collection($created)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Stream an attachment file back to an authenticated actor.
     *
     * Authentication is enforced by the `auth:sanctum` middleware on the route
     * group, keeping uploaded files private. The attachment must belong to the
     * issue named in the route — a mismatch yields a 404 so attachment ids cannot
     * be probed across issues — and the backing file must still exist on the
     * non-public `local` disk. The file is returned as a streamed download under
     * its original client filename rather than its hashed storage name.
     */
    public function download(Issue $issue, IssueAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->issue_id === $issue->getKey(), Response::HTTP_NOT_FOUND);

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($attachment->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }
}
