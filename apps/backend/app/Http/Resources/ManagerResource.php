<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\District;
use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a Manager into a safe payload for API responses.
 *
 * This resource deliberately omits the password hash, remember token, and any
 * Sanctum access tokens. It is used for manager creation responses, where the
 * newly created manager is never issued a login token and must authenticate
 * separately through the shared login endpoint.
 *
 * @mixin Manager
 */
class ManagerResource extends JsonResource
{
    /**
     * Disable wrapping so the manager payload is returned without an extra
     * `data` envelope, matching the shared auth profile shape.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Manager $manager */
        $manager = $this->resource;

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
            'is_main_manager' => (bool) $manager->is_main_manager,
            'created_by_manager_id' => $manager->created_by_manager_id,
            'district' => $this->compactDistrict($manager),
        ];
    }

    /**
     * Return a compact district payload only when the relation has already
     * been loaded on the model, avoiding unintended lazy queries.
     *
     * @return array<string, mixed>|null
     */
    protected function compactDistrict(Manager $manager): ?array
    {
        if (! $manager->relationLoaded('district')) {
            return null;
        }

        $district = $manager->getRelation('district');

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
