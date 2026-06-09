<?php

namespace App\Http\Resources;

use App\Enums\ActorType;
use App\Models\Department;
use App\Models\District;
use App\Models\Officer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an Officer into a safe payload for API responses.
 *
 * This resource deliberately omits the password hash, remember token, and any
 * Sanctum access tokens. Departments and districts are included only when their
 * relations have already been eager loaded to avoid lazy queries.
 *
 * @mixin Officer
 */
class OfficerResource extends JsonResource
{
    /**
     * Disable wrapping so a single officer payload is returned without an extra
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
        /** @var Officer $officer */
        $officer = $this->resource;

        return [
            'actor_type' => ActorType::Officer->value,
            'id' => $officer->id,
            'username' => $officer->username,
            'email' => $officer->email,
            'badge_number' => $officer->badge_number,
            'is_active' => (bool) $officer->is_active,
            'departments' => $this->compactDepartments($officer),
            'districts' => $this->compactDistricts($officer),
        ];
    }

    /**
     * Return the officer's assigned departments as compact objects, only when
     * the `departments` relation has already been loaded to avoid lazy queries.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactDepartments(Officer $officer): array
    {
        if (! $officer->relationLoaded('departments')) {
            return [];
        }

        return $officer->getRelation('departments')
            ->map(static fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Return the officer's assigned districts as compact objects, only when
     * the `districts` relation has already been loaded to avoid lazy queries.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function compactDistricts(Officer $officer): array
    {
        if (! $officer->relationLoaded('districts')) {
            return [];
        }

        return $officer->getRelation('districts')
            ->map(static fn (District $district): array => [
                'id' => $district->id,
                'name' => $district->name,
                'postal_prefix' => $district->postal_prefix,
            ])
            ->values()
            ->all();
    }
}
