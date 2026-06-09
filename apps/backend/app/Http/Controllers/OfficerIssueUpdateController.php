<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\IndexOfficerIssueUpdateRequest;
use App\Http\Requests\Issues\StoreOfficerIssueUpdateRequest;
use App\Http\Requests\Issues\UpdateOfficerIssueUpdateRequest;
use App\Http\Requests\Issues\DeleteOfficerIssueUpdateRequest;
use App\Http\Resources\OfficerIssueUpdateResource;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\OfficerIssueUpdate;
use App\Support\IssueVisibilityQuery;
use App\Support\OfficerIssueDistrictAccess;
use App\Support\OfficerIssueRowLock;
use App\Support\UploadedFileValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OfficerIssueUpdateController extends Controller
{
    private const DISK = 'local';

    private const DIRECTORY = 'officer-issue-update-attachments';

    private const MAX_COUNT = 3;

    /**
     * @var array<int, string>
     */
    private const UPDATE_RELATIONS = ['officer', 'attachments'];

    /**
     * Display a paginated listing of officer updates for a given issue.
     */
    public function index(IndexOfficerIssueUpdateRequest $request, Issue $issue): AnonymousResourceCollection
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $updates = $issue->officerUpdates()
            ->with(self::UPDATE_RELATIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return OfficerIssueUpdateResource::collection($updates);
    }

    /**
     * Store a newly created officer update.
     */
    public function store(StoreOfficerIssueUpdateRequest $request, Issue $issue): JsonResponse
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
            $update = OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use (
                $officer,
                $validated,
                $files,
                &$pathsWrittenDuringRequest,
            ) {
                OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, 'Only the assigned officer may submit updates for this issue.');

                $update = $lockedIssue->officerUpdates()->create([
                    'officer_id' => $officer->getKey(),
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                ]);

                if ($files !== []) {
                    $pathsWrittenDuringRequest = self::attachUploadedFiles($update, $files);
                }

                return $update;
            });
        } catch (Throwable $exception) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw $exception;
        }

        $update->load(self::UPDATE_RELATIONS);

        return (new OfficerIssueUpdateResource($update))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified officer update.
     */
    public function update(
        UpdateOfficerIssueUpdateRequest $request,
        Issue $issue,
        OfficerIssueUpdate $officer_update,
    ): OfficerIssueUpdateResource {
        /** @var Officer $officer */
        $officer = $request->user();

        if ($officer_update->issue_id !== $issue->id) {
            abort(404);
        }

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
                $officer_update,
                $validated,
                $removeIds,
                $files,
                &$pathsToDelete,
                &$pathsWrittenDuringRequest,
            ): void {
                OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, 'Only the assigned officer may update updates for this issue.');

                if ($officer_update->officer_id !== $officer->getKey()) {
                    abort(Response::HTTP_FORBIDDEN);
                }

                self::assertAttachmentCapUnderLock($officer_update, $removeIds, count($files));

                $officer_update->update([
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                ]);

                if ($removeIds !== []) {
                    $attachmentsToRemove = $officer_update->attachments()
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
                    $pathsWrittenDuringRequest = self::attachUploadedFiles($officer_update, $files);
                }
            });
        } catch (Throwable $exception) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw $exception;
        }

        self::deleteDiskFiles($pathsToDelete);

        $officer_update->refresh()->load(self::UPDATE_RELATIONS);

        return new OfficerIssueUpdateResource($officer_update);
    }

    /**
     * Remove the specified officer update.
     */
    public function destroy(
        DeleteOfficerIssueUpdateRequest $request,
        Issue $issue,
        OfficerIssueUpdate $officer_update,
    ): JsonResponse {
        /** @var Officer $officer */
        $officer = $request->user();

        if ($officer_update->issue_id !== $issue->id) {
            abort(404);
        }

        if (! IssueVisibilityQuery::canViewIssue($issue, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $issue);

        $pathsToDelete = [];

        try {
            OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use (
                $officer,
                $officer_update,
                &$pathsToDelete,
            ): void {
                OfficerIssueRowLock::assertAssignee($officer, $lockedIssue, 'Only the assigned officer may delete updates for this issue.');

                if ($officer_update->officer_id !== $officer->getKey()) {
                    abort(Response::HTTP_FORBIDDEN);
                }

                $attachmentsToRemove = $officer_update->attachments()->get();

                $pathsToDelete = $attachmentsToRemove
                    ->pluck('file_path')
                    ->all();

                foreach ($attachmentsToRemove as $attachment) {
                    $attachment->delete();
                }

                $officer_update->delete();
            });
        } catch (Throwable $exception) {
            throw $exception;
        }

        self::deleteDiskFiles($pathsToDelete);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Assert attachment ownership and cumulative cap on a locked update row.
     *
     * @param  array<int, int>  $removeIds
     *
     * @throws ValidationException
     */
    private static function assertAttachmentCapUnderLock(
        OfficerIssueUpdate $update,
        array $removeIds,
        int $incomingCount,
    ): void {
        if ($removeIds !== []) {
            $ownedCount = $update->attachments()
                ->whereIn('id', $removeIds)
                ->count();

            if ($ownedCount !== count($removeIds)) {
                throw ValidationException::withMessages([
                    'remove_attachment_ids' => [
                        'One or more attachment ids do not belong to this update.',
                    ],
                ]);
            }
        }

        $existing = $update->attachments()->count();
        $remaining = $existing - count($removeIds);

        if ($remaining + $incomingCount > self::MAX_COUNT) {
            $allowed = max(0, self::MAX_COUNT - $remaining);

            throw ValidationException::withMessages([
                'files' => [
                    'This update can have at most '.self::MAX_COUNT.' attachments. '.
                    "After removals, you may upload {$allowed} more.",
                ],
            ]);
        }
    }

    /**
     * Store uploaded files to disk and create attachment rows inside the locked transaction.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string> Disk paths written during this batch (for rollback cleanup)
     */
    private static function attachUploadedFiles(OfficerIssueUpdate $update, array $files): array
    {
        $writtenPaths = [];

        foreach ($files as $file) {
            $path = $file->store(self::DIRECTORY.'/'.$update->getKey(), self::DISK);
            $writtenPaths[] = $path;

            try {
                $update->attachments()->create([
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
