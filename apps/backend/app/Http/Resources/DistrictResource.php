<?php

namespace App\Http\Resources;

use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a District into a payload for API responses.
 *
 * The payload exposes the district location/search metadata, active state, and
 * timestamps. Assignment/reference counts are included only when they have been
 * loaded by the controller, so the resource never triggers lazy count queries.
 *
 * @mixin District
 */
class DistrictResource extends JsonResource
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
        /** @var District $district */
        $district = $this->resource;

        return [
            'id' => $district->id,
            'hub_id' => $district->hub_id,
            'hub' => $this->compactHub($district),
            'name' => $district->name,
            'postal_prefix' => $district->postal_prefix,
            'center_lat' => $district->center_lat,
            'center_lng' => $district->center_lng,
            'radius_meters' => $district->radius_meters,
            'is_active' => (bool) $district->is_active,
            'managers_count' => $this->compactCount($district, 'managers'),
            'officers_count' => $this->compactCount($district, 'officers'),
            'issues_count' => $this->compactCount($district, 'issues'),
            'created_at' => $district->created_at,
            'updated_at' => $district->updated_at,
        ];
    }

    /**
     * Return the district's hub when the relation has already been loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactHub(District $district): ?array
    {
        if (! $district->relationLoaded('hub') || $district->hub === null) {
            return null;
        }

        return (new HubResource($district->hub))->resolve();
    }

    /**
     * Return a relation count only when it has been eager-loaded or the relation
     * itself is loaded. Otherwise return null to avoid an implicit query.
     */
    protected function compactCount(District $district, string $relation): ?int
    {
        $attribute = "{$relation}_count";

        if ($district->{$attribute} !== null) {
            return (int) $district->{$attribute};
        }

        if ($district->relationLoaded($relation)) {
            return $district->getRelation($relation)->count();
        }

        return null;
    }
}
