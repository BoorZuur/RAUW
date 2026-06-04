<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Department;
use App\Models\District;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an Issue into a payload for API responses.
 *
 * Anonymity contract
 * ------------------
 * Issues remain user-owned through `issues.user_id` even when reported
 * anonymously, but the owning user's identity must never leak for an anonymous
 * report. When `is_anonymous` is true the author is presented through the
 * stable, server-generated `anonymous_alias` (e.g. `Melder#1234`) and the
 * `user_id` is omitted entirely. Only when the report is not anonymous and the
 * `user` relation has been eager-loaded is the real author identity exposed as
 * `id` and `username` (with `display_name` equal to `username`).
 *
 * Relation summaries (`category`, `district`, `attachments`) and relation
 * counts are emitted only when the controller has loaded them, so the resource
 * never triggers an implicit lazy query.
 *
 * Department exposure
 * -------------------
 * An issue's departments are auto-assigned from its category through the pivot
 * source of truth and are read-only from the client. The public payload exposes
 * them as a `departments` array only; the legacy single `department` enum field
 * is no longer serialized.
 *
 * Priority
 * --------
 * The integer `priority` is server-derived from the issue category's
 * main-category priority (lower number = higher urgency) and is read-only in
 * API responses.
 *
 * Visibility
 * ----------
 * The string `visibility` (`visible` or `hidden`) is read-only in API responses.
 * Clients cannot set or change it through create or update requests.
 *
 * @mixin Issue
 */
class IssueResource extends JsonResource
{
    /**
     * Disable wrapping so collections and single resources share a flat shape,
     * consistent with the other API resources in this codebase.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Issue $issue */
        $issue = $this->resource;

        return [
            'id' => $issue->id,
            'title' => $issue->title,
            'content' => $issue->content,
            'category_id' => $issue->category_id,
            'district_id' => $issue->district_id,
            'departments' => $this->compactDepartments($issue),
            'status' => $issue->status,
            'visibility' => $issue->visibility->value,
            'priority' => $issue->priority,
            'postal_code' => $issue->postal_code,
            'address' => $issue->address,
            'latitude' => $issue->latitude,
            'longitude' => $issue->longitude,
            'is_anonymous' => (bool) $issue->is_anonymous,
            'author' => $this->compactAuthor($issue),
            'vote_count' => (int) $issue->vote_count,
            'participant_count' => (int) $issue->participant_count,
            'duplicate_count' => (int) $issue->duplicate_count,
            'category' => $this->compactCategory($issue),
            'district' => $this->compactDistrict($issue),
            'attachments' => $this->compactAttachments($issue),
            'created_at' => $issue->created_at,
            'updated_at' => $issue->updated_at,
            'resolved_at' => $issue->resolved_at,
        ];
    }

    /**
     * Return an ownership-safe author summary.
     *
     * Anonymous reports expose only the stable alias and never the owning
     * user's identity or id. Non-anonymous reports expose id and username
     * (display_name equals username) only when the `user` relation is loaded;
     * otherwise a minimal id reference is returned to avoid an implicit query.
     *
     * @return array<string, mixed>
     */
    protected function compactAuthor(Issue $issue): array
    {
        if ((bool) $issue->is_anonymous === true) {
            return [
                'is_anonymous' => true,
                'display_name' => $issue->anonymous_alias,
            ];
        }

        if ($issue->relationLoaded('user')) {
            $user = $issue->getRelation('user');

            if ($user instanceof User) {
                return [
                    'is_anonymous' => false,
                    'id' => $user->id,
                    'username' => $user->username,
                    'display_name' => $user->username,
                ];
            }
        }

        return [
            'is_anonymous' => false,
            'id' => $issue->user_id,
        ];
    }

    /**
     * Return a compact category summary only when the relation is loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactCategory(Issue $issue): ?array
    {
        if (! $issue->relationLoaded('category')) {
            return null;
        }

        $category = $issue->getRelation('category');

        if (! $category instanceof Category) {
            return null;
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
        ];
    }

    /**
     * Return a compact district summary only when the relation is loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactDistrict(Issue $issue): ?array
    {
        if (! $issue->relationLoaded('district')) {
            return null;
        }

        $district = $issue->getRelation('district');

        if (! $district instanceof District) {
            return null;
        }

        return [
            'id' => $district->id,
            'name' => $district->name,
        ];
    }

    /**
     * Return serialized attachments only when the relation is loaded.
     *
     * @return array<int, mixed>|null
     */
    protected function compactAttachments(Issue $issue): ?array
    {
        if (! $issue->relationLoaded('attachments')) {
            return null;
        }

        return IssueAttachmentResource::collection($issue->getRelation('attachments'))->resolve();
    }

    /**
     * Return compact department summaries only when the relation is loaded.
     *
     * The pivot is the source of truth for an issue's department assignments
     * and supports multiple departments per issue, so the payload always
     * exposes them as an array. Null is returned when the relation has not been
     * eager loaded, mirroring the other relation summaries and avoiding an
     * implicit lazy query.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function compactDepartments(Issue $issue): ?array
    {
        if (! $issue->relationLoaded('departments')) {
            return null;
        }

        return $issue->getRelation('departments')
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->values()
            ->all();
    }
}
