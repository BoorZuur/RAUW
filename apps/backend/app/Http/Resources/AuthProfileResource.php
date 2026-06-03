<?php

namespace App\Http\Resources;

use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

/**
 * Serializes an authenticated actor (User, Officer, or Manager) into the
 * canonical auth profile payload shared by login, officer/user registration,
 * and `GET /api/auth/me`.
 *
 * Canonical auth contract
 * -----------------------
 * Auth metadata lives on the top-level response wrapper, not inside `profile`:
 *   - `token_type` and `access_token` are present only on token-issuing
 *     responses (login + officer/user registration), set by the controllers.
 *   - `actor_type` is emitted once at the top level on every auth response
 *     (including `GET /api/auth/me`). It is intentionally NOT duplicated inside
 *     `profile` to keep a single source of truth.
 *
 * The `profile` payload below exposes only client-facing identity and role
 * fields. Internal access-control flags (`is_active`) and audit/provenance
 * fields (`created_by_manager_id`) are deliberately omitted: inactive actors
 * cannot authenticate, so the flag is never meaningful here, and provenance is
 * an internal concern surfaced through admin-specific endpoints instead.
 *
 * Profile shape by actor type:
 *   - User:    id, name, username, email
 *   - Officer: id, username, email, badge_number, district?
 *   - Manager: id, username, email, department, is_main_manager, district?
 *
 * Retained fields with tradeoffs (kept intentionally, covered by tests):
 *   - `badge_number` (Officer): the client displays officer identity.
 *   - `district`     (Officer + Manager): compact assigned-district display;
 *     embedded only when the relation is already loaded. `district.id` carries
 *     the identifier, so the redundant `district_id` is omitted.
 *   - `is_main_manager` (Manager): lets the client conditionally show
 *     manager-administration UI. Backend authorization remains the source of
 *     truth for protected actions regardless of this flag.
 */
class AuthProfileResource extends JsonResource
{
    /**
     * Disable wrapping so this resource can be embedded under a custom key
     * in the login response without an extra `data` wrapper.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $this->resource;

        return match (true) {
            $actor instanceof User => $this->serializeUser($actor),
            $actor instanceof Officer => $this->serializeOfficer($actor),
            $actor instanceof Manager => $this->serializeManager($actor),
            default => throw new InvalidArgumentException(
                'AuthProfileResource only supports User, Officer, or Manager models.'
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeOfficer(Officer $officer): array
    {
        return [
            'id' => $officer->id,
            'username' => $officer->username,
            'email' => $officer->email,
            'badge_number' => $officer->badge_number,
            'district' => $this->compactDistrict($officer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeManager(Manager $manager): array
    {
        return [
            'id' => $manager->id,
            'username' => $manager->username,
            'email' => $manager->email,
            'department' => $manager->department instanceof \BackedEnum
                ? $manager->department->value
                : $manager->department,
            'is_main_manager' => (bool) $manager->is_main_manager,
            'district' => $this->compactDistrict($manager),
        ];
    }

    /**
     * Return a compact district payload only when the relation has already
     * been loaded on the model, avoiding unintended lazy queries.
     *
     * @return array<string, mixed>|null
     */
    protected function compactDistrict(Officer|Manager $actor): ?array
    {
        if (! $actor->relationLoaded('district')) {
            return null;
        }

        $district = $actor->getRelation('district');

        if (! $district instanceof District) {
            return null;
        }

        return [
            'id' => $district->id,
            'name' => $district->name,
            'postal_prefix' => $district->postal_prefix,
        ];
    }
}
