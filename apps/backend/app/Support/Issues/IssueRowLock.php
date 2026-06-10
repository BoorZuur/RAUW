<?php

namespace App\Support\Issues;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;

class IssueRowLock
{
    /**
     * Run a callback inside a transaction with the issue row locked for update.
     *
     * @template T
     *
     * @param  callable(Issue): T  $callback
     * @return T
     */
    public static function withLockedIssue(Issue $issue, callable $callback): mixed
    {
        return DB::transaction(function () use ($issue, $callback): mixed {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $callback($lockedIssue);
        });
    }
}
