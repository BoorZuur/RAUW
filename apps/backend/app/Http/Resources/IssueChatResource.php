<?php

namespace App\Http\Resources;

use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\User;
use App\Support\Issues\IssueChatAliasResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueChat
 */
class IssueChatResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        $resource,
        protected ?Issue $canonicalIssue = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var IssueChat $chat */
        $chat = $this->resource;

        return [
            'id' => $chat->id,
            'issue_id' => $chat->issue_id,
            'user_id' => $chat->user_id,
            'status' => $chat->status->value,
            'partner' => $this->compactPartner($chat),
            'opened_by_officer_id' => $chat->opened_by_officer_id,
            'closed_by_officer_id' => $chat->closed_by_officer_id,
            'created_at' => $chat->created_at,
            'updated_at' => $chat->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function compactPartner(IssueChat $chat): ?array
    {
        if ($this->canonicalIssue === null) {
            return null;
        }

        if (! $chat->relationLoaded('user')) {
            return null;
        }

        $user = $chat->getRelation('user');

        if (! $user instanceof User) {
            return null;
        }

        return (new IssueChatAliasResolver)->forUserOnIssue($user, $this->canonicalIssue);
    }
}
