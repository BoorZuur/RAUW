<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issues\ShowOfficerIssueResolutionRequest;
use App\Http\Requests\Issues\StoreOfficerIssueResolutionRequest;
use App\Http\Requests\Issues\UpdateOfficerIssueResolutionRequest;
use App\Http\Resources\OfficerIssueResolutionResource;
use App\Models\Issue;
use App\Models\Officer;
use App\Support\IssueVisibilityQuery;
use App\Support\OfficerIssueConflict;
use App\Support\OfficerIssueDistrictAccess;
use App\Support\OfficerIssueRowLock;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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
            $resolution = OfficerIssueRowLock::withLockedIssue($issue, function (Issue $lockedIssue) use ($officer, $validated, $files) {
                OfficerIssueRowLock::assertNoResolution($lockedIssue);

                $resolution = $lockedIssue->officerResolution()->create([
                    'officer_id' => $officer->getKey(),
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                ]);

                foreach ($files as $file) {
                    $path = $file->store(self::DIRECTORY.'/'.$resolution->getKey(), self::DISK);

                    $resolution->attachments()->create([
                        'file_path' => $path,
                        'file_url' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_at' => Carbon::now(),
                    ]);
                }

                return $resolution;
            });
        } catch (UniqueConstraintViolationException) {
            throw OfficerIssueConflict::officerResolutionExists();
        } catch (QueryException $exception) {
            if (self::isOfficerResolutionIssueIdViolation($exception)) {
                throw OfficerIssueConflict::officerResolutionExists();
            }

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

        DB::transaction(function () use ($officer, $resolution, $validated, $files, $removeIds): void {
            $resolution->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'officer_id' => $officer->getKey(),
            ]);

            if ($removeIds !== []) {
                $attachmentsToRemove = $resolution->attachments()
                    ->whereIn('id', $removeIds)
                    ->get();

                $disk = Storage::disk(self::DISK);

                foreach ($attachmentsToRemove as $attachment) {
                    if ($disk->exists($attachment->file_path)) {
                        $disk->delete($attachment->file_path);
                    }

                    $attachment->delete();
                }
            }

            foreach ($files as $file) {
                $path = $file->store(self::DIRECTORY.'/'.$resolution->getKey(), self::DISK);

                $resolution->attachments()->create([
                    'file_path' => $path,
                    'file_url' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => Carbon::now(),
                ]);
            }
        });

        $resolution->refresh()->load(self::RESOLUTION_RELATIONS);

        return new OfficerIssueResolutionResource($resolution);
    }

    /**
     * Whether a query exception reflects an officer_issue_resolutions.issue_id unique violation.
     */
    private static function isOfficerResolutionIssueIdViolation(QueryException $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'officer_issue_resolutions')
            && str_contains($message, 'issue_id');
    }
}
