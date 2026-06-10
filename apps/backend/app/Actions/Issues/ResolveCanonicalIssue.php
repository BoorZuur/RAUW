<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Support\Issues\IssueDuplicateConflict;

class ResolveCanonicalIssue
{
    private const MAX_DEPTH = 10;

    public function resolve(Issue $issue): Issue
    {
        $visited = [];
        $current = $issue;
        $depth = 0;

        while ($current->duplicate_of_id !== null) {
            if ($depth >= self::MAX_DEPTH) {
                throw IssueDuplicateConflict::issueNotCanonical(
                    'Duplicate chain exceeds maximum depth.',
                );
            }

            $currentId = $current->getKey();

            if ($currentId !== null && in_array($currentId, $visited, true)) {
                throw IssueDuplicateConflict::issueNotCanonical(
                    'Duplicate chain contains a cycle.',
                );
            }

            if ($currentId !== null) {
                $visited[] = $currentId;
            }

            $current->loadMissing('duplicateOf');

            if ($current->duplicateOf === null) {
                throw IssueDuplicateConflict::duplicateTargetNotFound();
            }

            $current = $current->duplicateOf;
            $depth++;
        }

        return $current;
    }

    public function __invoke(Issue $issue): Issue
    {
        return $this->resolve($issue);
    }
}
