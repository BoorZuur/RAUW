<?php

namespace App\Actions\Issues;

use App\Enums\ChatStatus;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssueChatConflict;
use App\Support\Issues\IssueChatParticipantEligibility;
use App\Support\Notifications\NotifyChatOpened;
use App\Support\OfficerIssueRowLock;

class OpenIssueChat
{
    /**
     * Open or reopen a 1:1 chat between the assignee officer and an eligible user.
     */
    public function open(
        Officer $officer,
        Issue $canonical,
        User $partnerUser,
        ?NotifyChatOpened $notifyChatOpened = null,
    ): IssueChat {
        $notifyChatOpened ??= new NotifyChatOpened;

        return OfficerIssueRowLock::withLockedIssue($canonical, function (Issue $locked) use ($officer, $partnerUser, $notifyChatOpened): IssueChat {
            OfficerIssueRowLock::assertAssignee(
                $officer,
                $locked,
                'Only the assigned officer may open a chat on this issue.',
            );

            if ($locked->status === IssueStatus::Closed) {
                throw IssueChatConflict::issueClosed();
            }

            IssueChatParticipantEligibility::assertEligible($partnerUser, $locked);

            $chat = IssueChat::query()->firstOrCreate(
                [
                    'issue_id' => $locked->getKey(),
                    'user_id' => $partnerUser->getKey(),
                ],
                [
                    'status' => ChatStatus::Closed,
                ],
            );

            if ($chat->status === ChatStatus::Open) {
                return $chat;
            }

            $chat->update([
                'status' => ChatStatus::Open,
                'opened_by_officer_id' => $officer->getKey(),
                'closed_by_officer_id' => null,
            ]);

            $chat->refresh();

            $notifyChatOpened->notify($locked, $chat, $officer);

            return $chat;
        });
    }
}
