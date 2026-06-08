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
use App\Support\OfficerIssueRowLock;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OfficerIssueResolutionController extends Controller
{
    private const DISK = 'local';

    private const DIRECTORY = 'officer-issue-resolution-attachments';

    /**
     * @var array<int, string>
     */
    private const RESOLUTION_RELATIONS = ['officer', 'attachments'];

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

        try {
            $resolution = OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use ($officer, $validated) {
                OfficerIssueRowLock::assertNoResolution($lockedIssue);

                return $lockedIssue->officerResolution()->create([
                    'officer_id' => $officer->getKey(),
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (self::isOfficerResolutionIssueIdViolation($exception)) {
                throw OfficerIssueConflict::officerResolutionExists();
            }

            throw $exception;
        } catch (QueryException $exception) {
            if (self::isOfficerResolutionIssueIdViolation($exception)) {
                throw OfficerIssueConflict::officerResolutionExists();
            }

            throw $exception;
        }

        try {
            self::attachUploadedFiles($resolution, $files);
        } catch (Throwable $exception) {
            self::compensatingDeleteResolutionIfNoAttachments($resolution);

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

        $resolution = $issue->officerResolution;

        if ($resolution === null) {
            abort(404);
        }

        $validated = $request->validated();
        $files = $request->file('files', []);
        $removeIds = $validated['remove_attachment_ids'] ?? [];
        $pathsToDelete = [];

        DB::transaction(function () use ($officer, $resolution, $validated, $removeIds, &$pathsToDelete): void {
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
        });

        self::deleteDiskFiles($pathsToDelete);
        self::attachUploadedFiles($resolution, $files);

        $resolution->refresh()->load(self::RESOLUTION_RELATIONS);

        return new OfficerIssueResolutionResource($resolution);
    }

    /**
     * Store uploaded files to disk and create attachment rows (after transaction commit).
     *
     * @param  array<int, UploadedFile>  $files
     */
    private static function attachUploadedFiles(OfficerIssueResolution $resolution, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store(self::DIRECTORY.'/'.$resolution->getKey(), self::DISK);

            try {
                $resolution->attachments()->create([
                    'file_path' => $path,
                    'file_url' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => Carbon::now(),
                ]);
            } catch (Throwable $exception) {
                self::deleteDiskFiles([$path]);

                throw $exception;
            }
        }
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

    /**
     * Remove a resolution row left without attachments after a failed create upload.
     */
    private static function compensatingDeleteResolutionIfNoAttachments(OfficerIssueResolution $resolution): void
    {
        if ($resolution->attachments()->exists()) {
            return;
        }

        $resolution->delete();
    }

    /**
     * Whether a query exception reflects an officer_issue_resolutions.issue_id unique violation.
     */
    private static function isOfficerResolutionIssueIdViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'officer_issue_resolutions_issue_id_unique')) {
            return true;
        }

        return str_contains($message, 'officer_issue_resolutions')
            && str_contains($message, 'issue_id');
    }
}
