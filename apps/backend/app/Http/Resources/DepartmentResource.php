<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a Department into a payload for API responses.
 *
 * The payload exposes the stable `code`, the display `name`, the active state,
 * and timestamps. The assigned category count is included only when the
 * `categories` relation (or its aggregate) has been loaded, so the resource
 * never triggers an unintended lazy query.
 *
 * @mixin Department
 */
class DepartmentResource extends JsonResource
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
        /** @var Department $department */
        $department = $this->resource;

        return [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'is_active' => (bool) $department->is_active,
            'categories_count' => $this->compactCategoriesCount($department),
            'created_at' => $department->created_at,
            'updated_at' => $department->updated_at,
        ];
    }

    /**
     * Return the assigned category count only when it has been loaded.
     *
     * Supports either a `withCount('categories')` aggregate (exposed as
     * `categories_count`) or an already-loaded `categories` relation. When
     * neither is available the field is null so no lazy query is triggered.
     */
    protected function compactCategoriesCount(Department $department): ?int
    {
        if ($department->categories_count !== null) {
            return (int) $department->categories_count;
        }

        if ($department->relationLoaded('categories')) {
            return $department->getRelation('categories')->count();
        }

        return null;
    }
}
