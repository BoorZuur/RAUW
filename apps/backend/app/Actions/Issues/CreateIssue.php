<?php

namespace App\Actions\Issues;

use App\Enums\JoinedVia;
use App\Models\Category;
use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Models\User;
use App\Support\IssuePriorityResolver;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIssue
{
    /**
     * Create a canonical issue with creator participation in a single transaction.
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(User $author, array $validated): Issue
    {
        return DB::transaction(function () use ($author, $validated): Issue {
            $attributes = $this->buildIssueAttributes($author, $validated);
            $category = $this->resolveCategory((int) $attributes['category_id']);

            $attributes['priority'] = IssuePriorityResolver::fromCategory($category);
            $attributes['participant_count'] = 1;

            $issue = $this->persistIssue($attributes);
            $issue->syncDepartments($category->departmentIds());

            IssueParticipant::query()->create([
                'issue_id' => $issue->getKey(),
                'user_id' => $author->getKey(),
                'is_anonymous' => (bool) $issue->is_anonymous,
                'joined_via' => JoinedVia::Creator,
                'via_issue_id' => null,
                'joined_at' => now(),
            ]);

            return $issue;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function buildIssueAttributes(User $author, array $validated): array
    {
        $attributes = collect($validated)->only([
            'title',
            'content',
            'category_id',
            'district_id',
            'postal_code',
            'address',
            'latitude',
            'longitude',
            'is_anonymous',
        ])->all();

        $attributes['user_id'] = $author->getKey();

        $isAnonymous = (bool) ($attributes['is_anonymous'] ?? false);
        $attributes['is_anonymous'] = $isAnonymous;
        $attributes['anonymous_alias'] = null;

        return $attributes;
    }

    public function resolveCategory(int $categoryId): Category
    {
        return Category::query()
            ->with('departments')
            ->findOrFail($categoryId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function persistIssue(array $attributes): Issue
    {
        $isAnonymous = (bool) ($attributes['is_anonymous'] ?? false);

        if ($isAnonymous) {
            return $this->withUniqueAnonymousAlias(
                fn (string $alias): Issue => Issue::query()->create([
                    ...$attributes,
                    'anonymous_alias' => $alias,
                ]),
            );
        }

        return Issue::query()->create($attributes);
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $persistUsingAlias
     * @return T
     */
    private function withUniqueAnonymousAlias(callable $persistUsingAlias): mixed
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $persistUsingAlias($this->generateAnonymousAlias());
            } catch (UniqueConstraintViolationException) {
                if ($attempt >= $maxAttempts) {
                    abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Server error.');
                }
            } catch (QueryException $exception) {
                if (! $this->isAnonymousAliasIntegrityViolation($exception)) {
                    throw $exception;
                }

                if ($attempt >= $maxAttempts) {
                    abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Server error.');
                }
            }
        }

        abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Server error.');
    }

    private function generateAnonymousAlias(): string
    {
        return 'Melder#'.Str::upper(Str::random(8));
    }

    private function isAnonymousAliasIntegrityViolation(QueryException $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'issues_anonymous_alias_unique')
            || str_contains($message, 'anonymous_alias');
    }
}
