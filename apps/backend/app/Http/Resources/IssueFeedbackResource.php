<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Issues\IssueAnonymousDisplayName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isOwner = $viewer instanceof User && $viewer->id === $this->reviewer_user_id;

        if ($isOwner) {
            $reviewerData = [
                'is_anonymous' => false,
                'id' => $this->reviewer->id,
                'username' => $this->reviewer->username,
            ];
        } else {
            $this->loadMissing('issue');
            $reviewerData = IssueAnonymousDisplayName::derive($this->reviewer, $this->issue);
        }

        return [
            'id' => $this->id,
            'issue_id' => $this->issue_id,
            'is_satisfied' => $this->is_satisfied,
            'comment' => $this->comment,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'reviewer' => $reviewerData,
        ];
    }
}
