<?php

namespace App\Http\Controllers;

use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\IssueChatMessages\DownloadIssueMessageAttachmentRequest;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\IssueMessageAttachment;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueChatAccess;
use App\Support\Issues\IssueChatMessageAttachments;
use App\Support\OfficerIssueDistrictAccess;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueMessageAttachmentController extends Controller
{
    /**
     * Stream a chat message attachment to an authorized participant.
     */
    public function download(
        DownloadIssueMessageAttachmentRequest $request,
        Issue $issue,
        IssueChat $chat,
        IssueMessage $message,
        IssueMessageAttachment $attachment,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): StreamedResponse {
        $canonical = $resolveCanonicalIssue->resolve($issue);
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        if ($chat->issue_id !== $canonical->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        IssueChatAccess::assertCanViewChat($actor, $chat, $canonical);

        if ($message->issue_chat_id !== $chat->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($attachment->issue_message_id !== $message->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $disk = Storage::disk(IssueChatMessageAttachments::DISK);

        abort_unless($disk->exists($attachment->file_path), Response::HTTP_NOT_FOUND);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }
}
