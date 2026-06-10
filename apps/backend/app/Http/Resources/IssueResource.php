<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Department;
use App\Models\District;
use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\OfficerIssueResolution;
use App\Models\User;
use App\Support\Issues\IssueParticipantVisibility;
use App\Support\IssueVisibilityQuery;
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
 * The string `visibility` (`visible` or `hidden`) is read-only in user-facing
 * create and update requests. Active officers and managers may change it through
 * PATCH /issues/{issue}/visibility.
 *
 * Assignee
 * --------
 * The nullable integer `assigned_officer_id` is read-only in user-facing create
 * and update requests. Active officers may set or clear it through
 * POST /issues/{issue}/assign-self and POST /issues/{issue}/unassign-self.
 *
 * Status history
 * --------------
 * The `status_history` array is included only for active officers and managers
 * when the controller has eager-loaded the relation. Regular users never receive
 * status history in issue payloads.
 *
 * Officer resolution
 * ------------------
 * The optional `officer_resolution` object is included when the controller has
 * eager-loaded the relation and the actor may view the issue. GET
 * `/issues/{issue}/officer-resolution` is the primary read path.
 *
 * Participant visibility
 * ----------------------
 * Participants viewing a canonical they joined (but do not own) receive status,
 * resolution, counters, and participation context while title, content,
 * location, author, and attachments are redacted. Officers and managers are never
 * redacted; owners always receive the full payload for issues they own.
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
        $visibility = IssueParticipantVisibility::for($issue, $request->user());
        $redact = $visibility->shouldRedactCanonicalContent();

        return array_merge([
            'id' => $issue->id,
            'title' => $redact ? null : $issue->title,
            'content' => $redact ? null : $issue->content,
            'category_id' => $issue->category_id,
            'district_id' => $issue->district_id,
            'departments' => $this->compactDepartments($issue),
            'status' => $issue->status,
            'assigned_officer_id' => $issue->assigned_officer_id,
            'visibility' => $issue->visibility->value,
            'priority' => $issue->priority,
            'postal_code' => $redact ? null : $issue->postal_code,
            'address' => $redact ? null : $issue->address,
            'latitude' => $redact ? null : $issue->latitude,
            'longitude' => $redact ? null : $issue->longitude,
            'is_anonymous' => $redact ? null : (bool) $issue->is_anonymous,
            'author' => $redact
                ? ['is_participant' => true]
                : $this->compactAuthor($issue),
            'participant_count' => (int) $issue->participant_count,
            'duplicate_count' => (int) $issue->duplicate_count,
            'category' => $this->compactCategory($issue),
            'district' => $this->compactDistrict($issue),
            'attachments' => $redact ? null : $this->compactAttachments($issue),
            'created_at' => $issue->created_at,
            'updated_at' => $issue->updated_at,
            'resolved_at' => $issue->resolved_at,
        ], $this->maybeDuplicateOfId($issue, $visibility), $visibility->contextFlags(), $this->maybeStatusHistory($issue, $request), $this->maybeOfficerResolution($issue, $request), $this->maybeFeedback($issue, $request));
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
     * Expose duplicate linkage to owners of child issues and to officers/managers.
     *
     * @return array<string, mixed>
     */
    protected function maybeDuplicateOfId(Issue $issue, IssueParticipantVisibility $visibility): array
    {
        if (! $visibility->canExposeDuplicateOfId()) {
            return [];
        }

        return [
            'duplicate_of_id' => $issue->duplicate_of_id,
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
     * Include status history only for officers and managers when eager loaded.
     *
     * @return array<string, mixed>
     */
    protected function maybeStatusHistory(Issue $issue, Request $request): array
    {
        $actor = $request->user();

        if (! ($actor instanceof Officer || $actor instanceof Manager)) {
            return [];
        }

        if (! $issue->relationLoaded('statusHistory')) {
            return [];
        }

        return [
            'status_history' => IssueStatusHistoryResource::collection($issue->getRelation('statusHistory'))->resolve(),
        ];
    }

    /**
     * Include officer resolution when eager loaded and the actor may view the issue.
     *
     * @return array<string, mixed>
     */
    protected function maybeOfficerResolution(Issue $issue, Request $request): array
    {
        if (! IssueVisibilityQuery::canViewIssue($issue, $request->user())) {
            return [];
        }

        if (! $issue->relationLoaded('officerResolution')) {
            return [];
        }

        $resolution = $issue->getRelation('officerResolution');

        if (! $resolution instanceof OfficerIssueResolution) {
            return ['officer_resolution' => null];
        }

        return [
            'officer_resolution' => (new OfficerIssueResolutionResource($resolution))->resolve(),
        ];
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

    /**
     * Include feedback for users (my_feedback) and managers (feedback array) when gesloten.
     *
     * @return array<string, mixed>
     */
    protected function maybeFeedback(Issue $issue, Request $request): array
    {
        if ($issue->status !== \App\Enums\IssueStatus::Closed) {
            return [];
        }

        if (! $issue->relationLoaded('feedback')) {
            return [];
        }

        $actor = $request->user();

        if ($actor instanceof User) {
            $myFeedback = $issue->getRelation('feedback')->first();
            return [
                'my_feedback' => $myFeedback ? (new IssueFeedbackResource($myFeedback))->resolve() : null,
            ];
        }

        if ($actor instanceof Manager) {
            return [
                'feedback' => IssueFeedbackResource::collection($issue->getRelation('feedback'))->resolve(),
            ];
        }

        return [];
    }
}
