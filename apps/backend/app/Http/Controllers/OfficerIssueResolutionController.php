<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\ShowOfficerIssueResolutionRequest;
use App\Http\Requests\Issues\StoreOfficerIssueResolutionRequest;
use App\Http\Requests\Issues\UpdateOfficerIssueResolutionRequest;
use App\Http\Resources\OfficerIssueResolutionResource;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Support\IssueVisibilityQuery;
use App\Support\OfficerIssueConflict;
use App\Support\OfficerIssueDistrictAccess;
use App\Support\OfficerIssueResolutionAttachments;
use App\Support\OfficerIssueRowLock;
use App\Support\UploadedFileValidator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @group Officer Actions on Issues
 */
class OfficerIssueResolutionController extends Controller
{
    private const DISK = 'local';

    private const DIRECTORY = 'officer-issue-resolution-attachments';

    /**
     * @var array<int, string>
     */
    private const RESOLUTION_RELATIONS = ['officer', 'attachments'];

    private const RESOLUTION_ASSIGNEE_MESSAGE = 'Only the assigned officer may submit or update the resolution for this issue.';

    /**
     * Show the single officer resolution for an issue.
     */
    public function show(ShowOfficerIssueResolutionRequest $request, Issue $issue): OfficerIssueResolutionResource
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $resolution = $issue->officerResolution()
            ->with(self::RESOLUTION_RELATIONS)
            ->first();

        if ($resolution === null) {
            abort(404);
        }

        return new OfficerIssueResolutionResource($resolution);
    }

    /**
     * Create the officer resolution report for an issue (once per issue).
     */
    public function store(StoreOfficerIssueResolutionRequest $request, Issue $issue): JsonResponse
    {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        $validated = $request->validated();
        $files = $request->file('files', []);
        $pathsWrittenDuringRequest = [];

        try {
            $resolution = OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use (
                $officer,
                $validated,
                $files,
                &$pathsWrittenDuringRequest,
            ) {
                OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, self::RESOLUTION_ASSIGNEE_MESSAGE);
                OfficerIssueRowLock::assertResolutionWritable($lockedIssue);
                OfficerIssueRowLock::assertNoResolution($lockedIssue);

                $resolution = $lockedIssue->officerResolution()->create([
                    'officer_id' => $officer->getKey(),
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                ]);

                if ($files !== []) {
                    $pathsWrittenDuringRequest = self::attachUploadedFiles($resolution, $files);
                }

                return $resolution;
            });
        } catch (UniqueConstraintViolationException) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw OfficerIssueConflict::officerResolutionExists();
        } catch (Throwable $exception) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw $exception;
        }

        $resolution->load(self::RESOLUTION_RELATIONS);

        return (new OfficerIssueResolutionResource($resolution))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the officer resolution report and manage image attachments.
     */
    public function update(UpdateOfficerIssueResolutionRequest $request, Issue $issue): OfficerIssueResolutionResource
    {
        /** @var Officer $officer */
        $officer = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        $validated = $request->validated();
        $files = $request->file('files', []);
        $removeIds = $validated['remove_attachment_ids'] ?? [];
        $pathsToDelete = [];
        $pathsWrittenDuringRequest = [];

        try {
            OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use (
                $officer,
                $validated,
                $removeIds,
                $files,
                &$pathsToDelete,
                &$pathsWrittenDuringRequest,
            ): void {
                OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, self::RESOLUTION_ASSIGNEE_MESSAGE);
                OfficerIssueRowLock::assertResolutionWritable($lockedIssue);

                $resolution = $lockedIssue->officerResolution;

                if ($resolution === null) {
                    abort(404);
                }

                self::assertAttachmentCapUnderLock($resolution, $removeIds, count($files));

                $resolution->update([
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                    'officer_id' => $officer->getKey(),
                ]);

                if ($removeIds !== []) {
                    $attachmentsToRemove = $resolution->attachments()
                        ->whereIn('id', $removeIds)
                        ->get();

                    $pathsToDelete = $attachmentsToRemove
                        ->pluck('file_path')
                        ->all();

                    foreach ($attachmentsToRemove as $attachment) {
                        $attachment->delete();
                    }
                }

                if ($files !== []) {
                    $pathsWrittenDuringRequest = self::attachUploadedFiles($resolution, $files);
                }
            });
        } catch (Throwable $exception) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw $exception;
        }

        self::deleteDiskFiles($pathsToDelete);

        $resolution = $issue->officerResolution()
            ->with(self::RESOLUTION_RELATIONS)
            ->firstOrFail();

        return new OfficerIssueResolutionResource($resolution);
    }

    /**
     * Assert attachment ownership and cumulative cap on a locked resolution row.
     *
     * @param  array<int, int>  $removeIds
     *
     * @throws ValidationException
     */
    private static function assertAttachmentCapUnderLock(
        OfficerIssueResolution $resolution,
        array $removeIds,
        int $incomingCount,
    ): void {
        if ($removeIds !== []) {
            $ownedCount = $resolution->attachments()
                ->whereIn('id', $removeIds)
                ->count();

            if ($ownedCount !== count($removeIds)) {
                throw ValidationException::withMessages([
                    'remove_attachment_ids' => [
                        'One or more attachment ids do not belong to this resolution.',
                    ],
                ]);
            }
        }

        $existing = $resolution->attachments()->count();
        $remaining = $existing - count($removeIds);

        if ($remaining + $incomingCount > OfficerIssueResolutionAttachments::MAX_COUNT) {
            $allowed = max(0, OfficerIssueResolutionAttachments::MAX_COUNT - $remaining);

            throw ValidationException::withMessages([
                'files' => [
                    'This resolution can have at most '.OfficerIssueResolutionAttachments::MAX_COUNT.' attachments. '.
                    "After removals, you may upload {$allowed} more.",
                ],
            ]);
        }
    }

    /**
     * Store uploaded files to disk and create attachment rows inside the locked transaction.
     *
     * Lock duration includes disk I/O; this prevents concurrent requests from exceeding the attachment cap.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string> Disk paths written during this batch (for rollback cleanup)
     */
    private static function attachUploadedFiles(OfficerIssueResolution $resolution, array $files): array
    {
        $writtenPaths = [];

        foreach ($files as $file) {
            $path = $file->store(self::DIRECTORY.'/'.$resolution->getKey(), self::DISK);
            $writtenPaths[] = $path;

            try {
                $resolution->attachments()->create([
                    'file_path' => $path,
                    'file_url' => null,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => UploadedFileValidator::detectMimeType($file),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => Carbon::now(),
                ]);
            } catch (Throwable $exception) {
                self::deleteDiskFiles($writtenPaths);

                throw $exception;
            }
        }

        return $writtenPaths;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private static function deleteDiskFiles(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $disk = Storage::disk(self::DISK);

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
