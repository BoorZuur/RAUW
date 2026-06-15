<?php

namespace App\Actions\Issues;

use App\Enums\JoinedVia;
use App\Enums\Visibility;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\IssuePriorityResolver;
use App\Support\IssueVisibilityQuery;
use App\Support\Issues\IssueDuplicateAssertions;
use App\Support\Issues\IssueDuplicateConflict;
use App\Support\Issues\IssueRowLock;

class CreateIssueAsDuplicate
{
    public function __construct(
        private readonly CreateIssue $createIssue,
        private readonly ResolveCanonicalIssue $resolveCanonicalIssue,
    ) {}

    /**
     * Create a hidden duplicate child linked to a canonical issue.
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(User $author, array $validated, int $duplicateOfId): Issue
    {
        $target = Issue::query()->find($duplicateOfId);

        if ($target === null) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        if (! IssueVisibilityQuery::canViewIssue($target, $author)) {
            throw IssueDuplicateConflict::duplicateTargetNotFound();
        }

        $canonical = $this->resolveCanonicalIssue->resolve($target);

        IssueDuplicateAssertions::assertMatchableStatus($canonical);
        IssueDuplicateAssertions::assertNotSelfDuplicate($author, $canonical);

        $attributes = $this->createIssue->buildIssueAttributes($author, $validated);
        $category = $this->createIssue->resolveCategory((int) $attributes['category_id']);
        $attributes['priority'] = IssuePriorityResolver::fromCategory($category);

        return IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical) use (
            $author,
            $attributes,
            $category,
        ): Issue {
            IssueDuplicateAssertions::assertMatchableStatus($lockedCanonical);

            $childAttributes = [
                ...$attributes,
                'visibility' => Visibility::Hidden,
                'duplicate_of_id' => $lockedCanonical->getKey(),
            ];

            $child = $this->createIssue->persistIssue($childAttributes);
            $child->syncDepartments($category->departmentIds());

            $lockedCanonical->increment('duplicate_count');

            $alreadyParticipant = IssueParticipant::query()
                ->where('issue_id', $lockedCanonical->getKey())
                ->where('user_id', $author->getKey())
                ->exists();

            if (! $alreadyParticipant) {
                IssueParticipant::query()->create([
                    'issue_id' => $lockedCanonical->getKey(),
                    'user_id' => $author->getKey(),
                    'is_anonymous' => (bool) $child->is_anonymous,
                    'joined_via' => JoinedVia::Duplicate,
                    'via_issue_id' => $child->getKey(),
                    'joined_at' => now(),
                ]);

                $lockedCanonical->increment('participant_count');
            }

            return $child;
        });
    }
}
