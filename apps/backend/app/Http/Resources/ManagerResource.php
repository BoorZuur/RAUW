<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\Department;
use App\Models\District;
use App\Models\Hub;
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
            'is_active' => (bool) $manager->is_active,
            'is_main_manager' => (bool) $manager->is_main_manager,
            'created_by_manager_id' => $manager->created_by_manager_id,
            'hub_id' => $manager->hub_id,
            'hub' => $this->compactHub($manager),
            'departments' => $this->compactDepartments($manager),
            'districts' => $this->compactDistricts($manager),
        ];
    }

    /**
     * Return the manager's hub when the relation has already been loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactHub(Manager $manager): ?array
    {
        if (! $manager->relationLoaded('hub') || ! $manager->hub instanceof Hub) {
            return null;
        }

        return (new HubResource($manager->hub))->resolve();
    }

    /**
     * Return the manager's assigned departments as compact objects, only when
     * the `departments` relation has already been loaded to avoid lazy queries.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactDepartments(Manager $manager): array
    {
        if (! $manager->relationLoaded('departments')) {
            return [];
        }

        return $manager->getRelation('departments')
            ->map(static fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Return the manager's assigned districts as compact objects, only when
     * the `districts` relation has already been loaded to avoid lazy queries.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactDistricts(Manager $manager): array
    {
        if (! $manager->relationLoaded('districts')) {
            return [];
        }

        return $manager->getRelation('districts')
            ->map(static fn (District $district): array => [
                'id' => $district->id,
                'name' => $district->name,
                'postal_prefix' => $district->postal_prefix,
            ])
            ->values()
            ->all();
    }
}
