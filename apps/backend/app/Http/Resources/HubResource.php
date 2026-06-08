<?php

namespace App\Http\Resources;

use App\Models\Hub;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a Hub into a payload for API responses.
 *
 * Reference counts are included only when they have been loaded by the
 * controller, so the resource never triggers lazy count queries.
 *
 * @mixin Hub
 */
class HubResource extends JsonResource
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
        /** @var Hub $hub */
        $hub = $this->resource;

        return [
            'id' => $hub->id,
            'name' => $hub->name,
            'address' => $hub->address,
            'postal_code' => $hub->postal_code,
            'latitude' => $hub->latitude,
            'longitude' => $hub->longitude,
            'is_active' => $hub->is_active,
            'districts_count' => $this->compactCount($hub, 'districts'),
            'officers_count' => $this->compactCount($hub, 'officers'),
            'managers_count' => $this->compactCount($hub, 'managers'),
            'created_at' => $hub->created_at,
            'updated_at' => $hub->updated_at,
        ];
    }

    /**
     * Return a relation count only when it has been eager-loaded or the relation
     * itself is loaded. Otherwise return null to avoid an implicit query.
     */
    protected function compactCount(Hub $hub, string $relation): ?int
    {
        $attribute = "{$relation}_count";

        if ($hub->{$attribute} !== null) {
            return (int) $hub->{$attribute};
        }

        if ($hub->relationLoaded($relation)) {
            return $hub->getRelation($relation)->count();
        }

        return null;
    }
}
