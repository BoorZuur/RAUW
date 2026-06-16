<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\CreateIssueAsDuplicate;
use App\Actions\Issues\DeleteDuplicateChild;
use App\Actions\Issues\ReparentOnCanonicalDelete;
use App\Enums\Visibility;
use App\Http\Requests\Issues\DeleteIssueRequest;
use App\Http\Requests\Issues\IndexIssueRequest;
use App\Http\Requests\Issues\ShowIssueRequest;
use App\Http\Requests\Issues\StoreIssueRequest;
use App\Http\Requests\Issues\UpdateIssueRequest;
use App\Http\Requests\Issues\UpdateIssueVisibilityRequest;
use App\Support\Notifications\NotifyIssueHidden;
use App\Support\Issues\IssueListScope;
use App\Support\IssueVisibilityQuery;
use App\Http\Resources\IssueResource;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Support\IssuePriorityResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * @group Issues
 */
class IssueController extends Controller
{
    /**
     * The relations eager loaded for every issue payload to avoid N+1 queries.
     *
     * Includes `officerResolution` with nested `officer` and `attachments` on
     * list and show so `officer_resolution` is embedded when a report exists.
     * `statusHistory` is loaded separately on show for officers and managers only.
     *
     * @var array<int, string>
     */
    private const ISSUE_RELATIONS = [
        'user',
        'category',
        'district',
        'departments',
        'attachments',
        'officerResolution.officer',
        'officerResolution.attachments',
    ];

    /**
     * List issues with composable filters, visibility scoping, and pagination.
     *
     * Results are visibility-scoped per actor type before optional filters:
     * users see visible canonicals, their own issues (any visibility), or
     * canonicals they participate on; officers and ordinary managers see issues
     * in assigned districts (including hidden); main managers see all issues
     * city-wide. Duplicate child rows are excluded by default per actor
     * (`IssueListScope`); users may use `participating=1` for owned children
     * with canonical participation, `followed=1` for deduped followed stories,
     * and officers/managers may use `include_duplicates=1` to include children.
     * A single Eloquent query applies the optional `district_id`, `department`,
     * `category_id`, `status`, `assigned_officer_id`, `unassigned`, `mine`,
     * `participating`, `followed`, `include_duplicates`, and `visibility`
     * filters conditionally and cumulatively (AND with visibility), so any
     * subset (or all) of the filters may be combined to narrow the result set.
     * The `department` filter is resolved through the issue departments
     * relationship with any-match semantics. Results are eager loaded (including
     * `officer_resolution` with officer and attachments when a report exists;
     * `status_history` is omitted on list), ordered newest-first by `created_at`
     * then `id`, and paginated with a safe default `per_page`.
     */
    public function index(IndexIssueRequest $request): AnonymousResourceCollection
    {
        $issues = IssueListScope::apply(
            IssueVisibilityQuery::applyVisibilityScope(
                Issue::query()->with(self::ISSUE_RELATIONS),
                $request->user(),
            ),
            $request->user(),
            $request,
        )
            ->when(
                $request->filled('district_id'),
                fn ($query) => $query->where('district_id', $request->integer('district_id')),
            )
            ->when(
                $request->filled('department'),
                fn ($query) => $query->whereHas(
                    'departments',
                    fn ($departmentQuery) => $departmentQuery->where('code', (string) $request->input('department')),
                ),
            )
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->integer('category_id')),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->input('status')),
            )
            ->when(
                $request->filled('assigned_officer_id'),
                fn ($query) => $query->where('assigned_officer_id', $request->integer('assigned_officer_id')),
            )
            ->when(
                $request->wantsUnassigned(),
                fn ($query) => $query->whereNull('assigned_officer_id'),
            )
            ->when(
                $request->wantsMine(),
                function ($query) use ($request): void {
                    /** @var User $actor */
                    $actor = $request->user();

                    $query->where('user_id', $actor->getKey());
                },
            )
            ->when(
                $request->filled('visibility'),
                fn ($query) => $query->where('visibility', $request->enum('visibility')),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->withQueryString();

        if ($request->wantsFollowed() && $request->user() instanceof User) {
            /** @var User $actor */
            $actor = $request->user();

            $issues->getCollection()->each(
                fn (Issue $issue) => $issue->loadActorParticipant($actor),
            );
        }

        return IssueResource::collection($issues);
    }

    /**
     * Create an issue on behalf of the authenticated regular user.
     *
     * Authorization (active user only) and validation are enforced by
     * StoreIssueRequest. Normal creates delegate to {@see CreateIssue} (creator
     * participant, `participant_count = 1`). When `duplicate_of_id` is present,
     * {@see CreateIssueAsDuplicate} creates a hidden child linked to the
     * canonical target and returns the child resource. The issue remains
     * user-owned through `user_id` even when reported anonymously. Departments
     * and `priority` are derived server-side from the selected category.
     * Anonymous reports receive a stable server-generated `anonymous_alias`.
     */
    public function store(
        StoreIssueRequest $request,
        CreateIssue $createIssue,
        CreateIssueAsDuplicate $createIssueAsDuplicate,
    ): JsonResponse {
        /** @var User $author */
        $author = $request->user();

        $validated = $request->safe()->only([
            'title',
            'content',
            'category_id',
            'district_id',
            'postal_code',
            'address',
            'latitude',
            'longitude',
            'is_anonymous',
        ]);

        $issue = $request->filled('duplicate_of_id')
            ? $createIssueAsDuplicate->create($author, $validated, $request->integer('duplicate_of_id'))
            : $createIssue->create($author, $validated);

        $issue->load(self::ISSUE_RELATIONS);

        return (new IssueResource($issue))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single issue with its eager-loaded relations.
     *
     * Visibility is enforced after route binding: users may view visible issues,
     * their own issues (any visibility), or canonical issues they participate on
     * (with canonical content redacted at the resource layer); officers and
     * ordinary managers may view issues in assigned districts; main managers
     * may view any issue city-wide. Unauthorized or invisible issues return 404
     * to avoid leaking existence. Embeds `officer_resolution` when a report
     * exists. Active officers and managers also receive `status_history` (newest
     * first); regular users do not.
     */
    public function show(ShowIssueRequest $request, Issue $issue): IssueResource
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $actor = $request->user();
        $issue->load(self::ISSUE_RELATIONS);

        if ($actor instanceof User) {
            $issue->loadActorParticipant($actor);

            if ($issue->status === \App\Enums\IssueStatus::Closed) {
                $issue->load(['feedback' => function ($query) use ($actor) {
                    $query->where('reviewer_user_id', $actor->id)->with('reviewer');
                }]);
            }
        }

        if ($actor instanceof Officer || $actor instanceof Manager) {
            $issue->load([
                'statusHistory' => fn ($query) => $query
                    ->with('changedByOfficer')
                    ->orderByDesc('changed_at'),
            ]);

            if ($actor instanceof Manager && $issue->status === \App\Enums\IssueStatus::Closed) {
                $issue->load(['feedback' => fn ($query) => $query->with(['reviewer', 'issue'])]);
            }
        }

        return new IssueResource($issue);
    }

    /**
     * Update an owner's issue, applying only the validated, owner-editable
     * fields.
     *
     * Ownership and authorization are enforced by UpdateIssueRequest. Only the
     * keys present in the validated payload are applied, so partial updates
     * leave untouched fields intact. When `category_id` is supplied the issue's
     * departments and integer `priority` are re-derived from the new category
     * and re-synced through the pivot source of truth (supporting multiple
     * departments per issue); departments and priority are never accepted from
     * the client. The `user_id` is never reassigned and the server-generated `anonymous_alias` is never accepted
     * from the client: toggling `is_anonymous` on lazily generates a stable
     * alias only when one does not already exist (so an existing alias is
     * preserved), while toggling it off clears the alias.
     */
    public function update(UpdateIssueRequest $request, Issue $issue): IssueResource
    {
        $attributes = $request->safe()->only([
            'title',
            'content',
            'category_id',
            'district_id',
            'postal_code',
            'address',
            'latitude',
            'longitude',
            'is_anonymous',
        ]);

        $category = null;

        if (array_key_exists('category_id', $attributes)) {
            $category = Category::query()
                ->with('departments')
                ->findOrFail($attributes['category_id']);

            $attributes['priority'] = IssuePriorityResolver::fromCategory($category);
        }

        if (array_key_exists('is_anonymous', $attributes)) {
            $isAnonymous = (bool) $attributes['is_anonymous'];
            $attributes['is_anonymous'] = $isAnonymous;

            if ($isAnonymous) {
                // Preserve an already-assigned stable alias; only mint a new one
                // when the issue is transitioning into the anonymous state.
                if ($issue->anonymous_alias === null) {
                    unset($attributes['anonymous_alias']);
                }
            } else {
                $attributes['anonymous_alias'] = null;
            }
        }

        $needsNewAnonymousAlias = array_key_exists('is_anonymous', $attributes)
            && (bool) $attributes['is_anonymous']
            && $issue->anonymous_alias === null;

        if ($attributes !== []) {
            $issue->update($attributes);
        }

        if ($needsNewAnonymousAlias) {
            $this->withUniqueAnonymousAlias(
                fn (string $alias): bool => $issue->update(['anonymous_alias' => $alias]),
            );
        }

        if ($category !== null) {
            $issue->syncDepartments($category->departmentIds());
        }

        $issue->refresh()->load(self::ISSUE_RELATIONS);

        return new IssueResource($issue);
    }

    /**
     * Set an issue's visibility to visible or hidden.
     *
     * Authorization is enforced by UpdateIssueVisibilityRequest (active officer or
     * manager only). Visibility scope is enforced after route binding via
     * IssueVisibilityQuery::canViewIssue(), matching show behavior: issues the
     * actor cannot view return 404. Only the visibility field is updated.
     */
    public function updateVisibility(
        UpdateIssueVisibilityRequest $request,
        Issue $issue,
        NotifyIssueHidden $notifyIssueHidden,
    ): IssueResource {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            abort(404);
        }

        $newVisibility = $request->enum('visibility', Visibility::class);
        $wasHidden = $issue->visibility === Visibility::Hidden;

        $issue->update([
            'visibility' => $newVisibility,
        ]);

        if ($newVisibility === Visibility::Hidden && ! $wasHidden) {
            $notifyIssueHidden->notify($issue, $request->user());
        }

        $issue->refresh()->load(self::ISSUE_RELATIONS);

        return new IssueResource($issue);
    }

    /**
     * Hard delete an owner's issue.
     *
     * Owner-only: officers and managers cannot delete issues they do not own
     * (enforced by {@see DeleteIssueRequest}). Duplicate children delegate to
     * {@see DeleteDuplicateChild} with optional `leave_participation` in the
     * request body (default false — keep canonical participation). Canonical
     * issues delegate to {@see ReparentOnCanonicalDelete}, which promotes the
     * oldest child when duplicates exist. This is a hard delete, not a soft
     * delete: rows are removed outright and attachment FK cascades apply.
     */
    public function destroy(
        DeleteIssueRequest $request,
        Issue $issue,
        DeleteDuplicateChild $deleteDuplicateChild,
        ReparentOnCanonicalDelete $reparentOnCanonicalDelete,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($issue->duplicate_of_id !== null) {
            $deleteDuplicateChild->delete($actor, $issue, $request->leaveParticipation());
        } else {
            $reparentOnCanonicalDelete->delete($issue);
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Generate a candidate anonymous alias such as `Melder#AB12CD34`.
     *
     * Uniqueness is enforced by the database unique index on `anonymous_alias`;
     * callers persist through {@see withUniqueAnonymousAlias()} so rare
     * collisions retry with a fresh candidate instead of pre-check queries.
     * The value fits within the `anonymous_alias` column (20 characters).
     */
    private function generateAnonymousAlias(): string
    {
        return 'Melder#'.Str::upper(Str::random(8));
    }

    /**
     * Persist issue data that includes a new `anonymous_alias`, retrying on
     * unique-index collisions up to a fixed attempt limit.
     *
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

    /**
     * Whether a query exception reflects an `anonymous_alias` unique violation.
     */
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
