<?php

namespace App\Http\Controllers;

use App\Actions\Issues\MarkIssueChatMessagesRead;
use App\Actions\Issues\ResolveCanonicalIssue;
use App\Actions\Issues\SendIssueMessage;
use App\Http\Requests\IssueChatMessages\IndexIssueChatMessagesRequest;
use App\Http\Requests\IssueChatMessages\MarkIssueChatMessagesReadRequest;
use App\Http\Requests\IssueChatMessages\StoreIssueChatMessageRequest;
use App\Http\Resources\IssueMessageResource;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueChatAccess;
use App\Support\OfficerIssueDistrictAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class IssueChatMessageController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const MESSAGE_RELATIONS = ['user', 'officer', 'attachments'];

    /**
     * List messages for a chat (open or closed).
     */
    public function index(
        IndexIssueChatMessagesRequest $request,
        Issue $issue,
        IssueChat $chat,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): AnonymousResourceCollection {
        $canonical = $resolveCanonicalIssue->resolve($issue);
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        IssueChatAccess::assertCanViewChat($actor, $chat, $canonical);

        $messages = $chat->messages()
            ->with(self::MESSAGE_RELATIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        $messages->getCollection()->transform(
            fn ($message) => new IssueMessageResource($message, $canonical, $chat->getKey()),
        );

        return IssueMessageResource::collection($messages);
    }

    /**
     * Send a message (text and/or attachments) in an open chat.
     */
    public function store(
        StoreIssueChatMessageRequest $request,
        Issue $issue,
        IssueChat $chat,
        ResolveCanonicalIssue $resolveCanonicalIssue,
        SendIssueMessage $sendIssueMessage,
    ): JsonResponse {
        $canonical = $resolveCanonicalIssue->resolve($issue);
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        $message = $sendIssueMessage->send(
            $actor,
            $canonical,
            $chat,
            $request->input('content'),
            $request->file('files', []),
        );

        $message->load(self::MESSAGE_RELATIONS);

        return (new IssueMessageResource($message, $canonical, $chat->getKey()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Mark the other party's unread messages as read in an open chat.
     */
    public function markRead(
        MarkIssueChatMessagesReadRequest $request,
        Issue $issue,
        IssueChat $chat,
        ResolveCanonicalIssue $resolveCanonicalIssue,
        MarkIssueChatMessagesRead $markIssueChatMessagesRead,
    ): JsonResponse {
        $canonical = $resolveCanonicalIssue->resolve($issue);
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        if ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        $markedRead = $markIssueChatMessagesRead->mark($actor, $canonical, $chat);

        return response()->json(['marked_read' => $markedRead]);
    }
}
