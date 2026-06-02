<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

/**
 * Serializes an authenticated actor (User, Officer, or Manager) into the
 * canonical login profile payload returned by the shared auth endpoint.
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
            'actor_type' => ActorType::User->value,
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
            'is_active' => (bool) $user->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeOfficer(Officer $officer): array
    {
        return [
            'actor_type' => ActorType::Officer->value,
            'id' => $officer->id,
            'username' => $officer->username,
            'email' => $officer->email,
            'badge_number' => $officer->badge_number,
            'district_id' => $officer->district_id,
            'is_active' => (bool) $officer->is_active,
            'district' => $this->compactDistrict($officer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeManager(Manager $manager): array
    {
        return [
            'actor_type' => ActorType::Manager->value,
            'id' => $manager->id,
            'username' => $manager->username,
            'email' => $manager->email,
            'department' => $manager->department instanceof \BackedEnum
                ? $manager->department->value
                : $manager->department,
            'district_id' => $manager->district_id,
            'is_active' => (bool) $manager->is_active,
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
