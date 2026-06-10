<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityPosts\DeleteCommunityPostAttachmentRequest;
use App\Http\Requests\CommunityPosts\DownloadCommunityPostAttachmentRequest;
use App\Http\Requests\CommunityPosts\StoreCommunityPostAttachmentRequest;
use App\Http\Resources\CommunityPostAttachmentResource;
use App\Models\CommunityPost;
use App\Models\CommunityPostAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CommunityPostAttachmentController extends Controller
{
    /**
     * Store new attachments for a community post.
     */
    public function store(StoreCommunityPostAttachmentRequest $request, CommunityPost $communityPost): JsonResponse
    {
        // Enforce the 5 file limit
        $currentCount = $communityPost->attachments()->count();
        $uploadCount = count($request->file('attachments'));

        if ($currentCount + $uploadCount > 5) {
            return response()->json([
                'message' => 'A community post may have at most 5 attachments. This upload exceeds that limit.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resources = [];

        foreach ($request->file('attachments') as $file) {
            // Development constraint: explicitly store outside the public folder
            $path = $file->store('community-post-attachments', 'local');

            $attachment = $communityPost->attachments()->create([
                'file_url' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            $resources[] = new CommunityPostAttachmentResource($attachment);
        }

        return response()->json($resources, Response::HTTP_CREATED);
    }

    /**
     * Download the specified attachment.
     */
    public function download(DownloadCommunityPostAttachmentRequest $request, CommunityPost $communityPost, CommunityPostAttachment $attachment)
    {
        if ($attachment->community_post_id !== $communityPost->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! Storage::disk('local')->exists($attachment->file_url)) {
            abort(Response::HTTP_NOT_FOUND, 'The requested file could not be found on disk.');
        }

        return Storage::disk('local')->download(
            $attachment->file_url,
            $attachment->original_name,
            ['Content-Type' => $attachment->file_type]
        );
    }

    /**
     * Remove the specified attachment from storage.
     */
    public function destroy(DeleteCommunityPostAttachmentRequest $request, CommunityPost $communityPost, CommunityPostAttachment $attachment): JsonResponse
    {
        if ($attachment->community_post_id !== $communityPost->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Remove from storage if it exists
        if (Storage::disk('local')->exists($attachment->file_url)) {
            Storage::disk('local')->delete($attachment->file_url);
        }

        $attachment->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
