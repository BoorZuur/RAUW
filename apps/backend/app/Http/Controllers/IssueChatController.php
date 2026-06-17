<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CloseIssueChat;
use App\Actions\Issues\OpenIssueChat;
use App\Actions\Issues\ResolveCanonicalIssue;
use App\Http\Requests\IssueChats\CloseIssueChatRequest;
use App\Http\Requests\IssueChats\IndexIssueChatsRequest;
use App\Http\Requests\IssueChats\OpenIssueChatRequest;
use App\Http\Resources\IssueChatResource;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssueChatConflict;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueChatAccess;
use App\Support\OfficerIssueConflict;
use App\Support\OfficerIssueDistrictAccess;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Issue Chats
 */
class IssueChatController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const CHAT_RELATIONS = ['user', 'openedByOfficer', 'closedByOfficer'];

    /**
     * List chats for a canonical issue.
     *
     * Assignee officers see all chats. Eligible users see only their own chat
     * (200 with an empty list when none exist). Non-assignee officers and
     * ineligible users receive 403.
     */
    public function index(
        IndexIssueChatsRequest $request,
        Issue $issue,
        ResolveCanonicalIssue $resolveCanonicalIssue,
    ): AnonymousResourceCollection {
        $canonical = $resolveCanonicalIssue->resolve($issue);
        $actor = $request->user();

        if (! IssueVisibilityQuery::canViewIssue($canonical, $actor)) {
            abort(404);
        }

        if (! IssueChatAccess::canListChatsOnIssue($actor, $canonical)) {
            if ($actor instanceof Officer) {
                throw OfficerIssueConflict::notAssignedOfficer(
                    'Only the assigned officer may list chats on this issue.',
                );
            }

            throw IssueChatConflict::notChatParticipant();
        }

        $query = $canonical->chats()
            ->with(self::CHAT_RELATIONS)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($actor instanceof User) {
            $query->where('user_id', $actor->getKey());
        } elseif ($actor instanceof Officer) {
            OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($actor, $canonical);
        }

        $chats = $query
            ->paginate($request->perPage())
            ->withQueryString();

        $chats->getCollection()->transform(
            fn (IssueChat $chat) => new IssueChatResource($chat, $canonical),
        );

        return IssueChatResource::collection($chats);
    }

    /**
     * Open or idempotently reopen a 1:1 chat with an eligible user.
     */
    public function open(
        OpenIssueChatRequest $request,
        Issue $issue,
        ResolveCanonicalIssue $resolveCanonicalIssue,
        OpenIssueChat $openIssueChat,
    ): IssueChatResource {
        /** @var Officer $officer */
        $officer = $request->user();

        $canonical = $resolveCanonicalIssue->resolve($issue);

        if (! IssueVisibilityQuery::canViewIssue($canonical, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $canonical);

        $partnerUser = User::query()->findOrFail($request->validated('user_id'));

        $chat = $openIssueChat->open($officer, $canonical, $partnerUser);
        $chat->load(self::CHAT_RELATIONS);

        return new IssueChatResource($chat, $canonical);
    }

    /**
     * Close a single chat on the canonical issue.
     */
    public function close(
        CloseIssueChatRequest $request,
        Issue $issue,
        IssueChat $chat,
        ResolveCanonicalIssue $resolveCanonicalIssue,
        CloseIssueChat $closeIssueChat,
    ): IssueChatResource {
        /** @var Officer $officer */
        $officer = $request->user();

        $canonical = $resolveCanonicalIssue->resolve($issue);

        if (! IssueVisibilityQuery::canViewIssue($canonical, $officer)) {
            abort(404);
        }

        OfficerIssueDistrictAccess::assertOfficerInIssueDistrict($officer, $canonical);

        $chat = $closeIssueChat->close($officer, $canonical, $chat);
        $chat->load(self::CHAT_RELATIONS);

        return new IssueChatResource($chat, $canonical);
    }
}
