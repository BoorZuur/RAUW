<?php

namespace App\Support;

use App\Enums\Visibility;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IssueVisibilityQuery
{
    /**
     * Restrict issue queries to rows the actor may list or show.
     *
     * Active users see visible canonical issues, issues they own (any
     * visibility), or canonical issues they participate on (content redaction
     * is applied at the resource layer). Active officers and managers see all
     * issues including hidden. Assumes the actor is active; inactive actors are
     * blocked by middleware before this runs.
     */
    public static function applyVisibilityScope(Builder $query, Model $actor): Builder
    {
        if ($actor instanceof Officer || $actor instanceof Manager) {
            return $query;
        }

        if ($actor instanceof User) {
            return $query->where(function (Builder $scoped) use ($actor): void {
                $scoped->where('visibility', Visibility::Visible)
                    ->orWhere('user_id', $actor->getKey())
                    ->orWhere(function (Builder $participantCanonical) use ($actor): void {
                        $participantCanonical
                            ->whereNull('duplicate_of_id')
                            ->whereExists(function ($participantQuery) use ($actor): void {
                                $participantQuery->selectRaw('1')
                                    ->from('issue_participants')
                                    ->whereColumn('issue_participants.issue_id', 'issues.id')
                                    ->where('issue_participants.user_id', $actor->getKey());
                            });
                    });
            });
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Whether the actor may view a single issue (show).
     *
     * Mirrors {@see applyVisibilityScope()} for one row: users may view visible
     * issues, their own issues regardless of visibility, or canonical issues
     * they participate on (content redaction is applied at the resource layer).
     * Officers and managers may view any issue.
     */
    public static function canViewIssue(Issue $issue, Model $actor): bool
    {
        if ($actor instanceof Officer || $actor instanceof Manager) {
            return true;
        }

        if ($actor instanceof User) {
            if ($issue->visibility === Visibility::Visible) {
                return true;
            }

            if ($issue->user_id === $actor->getKey()) {
                return true;
            }

            if ($issue->duplicate_of_id === null) {
                return IssueParticipant::query()
                    ->where('issue_id', $issue->getKey())
                    ->where('user_id', $actor->getKey())
                    ->exists();
            }

            return false;
        }

        return false;
    }
}
