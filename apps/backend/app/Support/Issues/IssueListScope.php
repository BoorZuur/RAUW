<?php

namespace App\Support\Issues;

use App\Http\Requests\Issues\IndexIssueRequest;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IssueListScope
{
    /**
     * Apply duplicate-related list filters for the authenticated actor.
     *
     * When `followed=1`, narrows to deduped followed canonical stories (owned
     * duplicate child when present, otherwise the canonical row). When
     * `participating=1`, narrows to owned duplicate children with active
     * canonical participation. Otherwise applies default duplicate exclusion
     * rules (users: hide others' children; officers/managers: hide children
     * unless `include_duplicates=1`).
     */
    public static function apply(Builder $query, Model $actor, IndexIssueRequest $request): Builder
    {
        if ($request->wantsFollowed()) {
            return self::applyFollowedFilter($query, $actor);
        }

        if ($request->wantsParticipating()) {
            return self::applyParticipatingFilter($query, $actor);
        }

        return self::applyDuplicateExclusion($query, $actor, $request);
    }

    /**
     * Exclude duplicate child rows according to actor-specific default browse rules.
     *
     * Users see canonical issues plus duplicate children they own. Officers and
     * managers see canonical issues only unless `include_duplicates=1` is set on
     * the request.
     */
    public static function applyDuplicateExclusion(
        Builder $query,
        Model $actor,
        IndexIssueRequest $request,
    ): Builder {
        if ($actor instanceof Officer || $actor instanceof Manager) {
            if ($request->wantsIncludeDuplicates()) {
                return $query;
            }

            return $query->whereNull('duplicate_of_id');
        }

        if ($actor instanceof User) {
            return $query->where(function (Builder $scoped) use ($actor): void {
                $scoped->whereNull('duplicate_of_id')
                    ->orWhere('user_id', $actor->getKey());
            });
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Restrict the list to owned duplicate children where the actor still
     * participates on the canonical parent.
     *
     * Manual-only participants (no owned child row) are excluded because this
     * filter requires `duplicate_of_id IS NOT NULL` and `user_id = actor`.
     */
    public static function applyParticipatingFilter(Builder $query, Model $actor): Builder
    {
        if (! $actor instanceof User) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->where('user_id', $actor->getKey())
            ->whereNotNull('duplicate_of_id')
            ->whereExists(function ($participantQuery) use ($actor): void {
                $participantQuery->selectRaw('1')
                    ->from('issue_participants')
                    ->whereColumn('issue_participants.issue_id', 'issues.duplicate_of_id')
                    ->where('issue_participants.user_id', $actor->getKey());
            });
    }

    /**
     * Restrict the list to deduped followed canonical stories for the actor.
     *
     * Returns owned duplicate children when the actor still participates on the
     * canonical parent; otherwise returns canonical issues the actor follows
     * (excluding own reports and rows where an owned child still exists).
     */
    public static function applyFollowedFilter(Builder $query, Model $actor): Builder
    {
        if (! $actor instanceof User) {
            return $query->whereRaw('0 = 1');
        }

        $userId = $actor->getKey();

        return $query->where(function (Builder $followed) use ($userId): void {
            $followed->where(function (Builder $scoped) use ($userId): void {
                $scoped->where('user_id', $userId)
                    ->whereNotNull('duplicate_of_id')
                    ->whereExists(function ($participantQuery) use ($userId): void {
                        $participantQuery->selectRaw('1')
                            ->from('issue_participants')
                            ->whereColumn('issue_participants.issue_id', 'issues.duplicate_of_id')
                            ->where('issue_participants.user_id', $userId);
                    });
            })->orWhere(function (Builder $scoped) use ($userId): void {
                $scoped->whereNull('duplicate_of_id')
                    ->where('user_id', '!=', $userId)
                    ->whereExists(function ($participantQuery) use ($userId): void {
                        $participantQuery->selectRaw('1')
                            ->from('issue_participants')
                            ->whereColumn('issue_participants.issue_id', 'issues.id')
                            ->where('issue_participants.user_id', $userId);
                    })
                    ->whereNotExists(function ($childQuery) use ($userId): void {
                        $childQuery->selectRaw('1')
                            ->from('issues as owned_children')
                            ->whereColumn('owned_children.duplicate_of_id', 'issues.id')
                            ->where('owned_children.user_id', $userId);
                    });
            });
        });
    }
}
