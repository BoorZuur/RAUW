<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a Category into a payload for API responses.
 *
 * The payload exposes the category hierarchy (`parent_id` with a compact
 * parent summary, and nested children when loaded), the active state, the
 * assigned departments, and the `priority` ordering field for main categories.
 * Subcategories order by `name` only. A lower `priority` means higher urgency.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'is_active' => (bool) $category->is_active,
            'parent_id' => $category->parent_id,
            'is_main_category' => $category->parent_id === null,
            // Lower number = higher priority. `priority` applies to main categories only.
            'priority' => $category->priority,
            'parent' => $this->compactParent($category),
            'departments' => $this->compactDepartments($category),
            'children' => $this->compactChildren($category),
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }

    /**
     * Return a compact parent summary only when the relation is loaded.
     *
     * @return array<string, mixed>|null
     */
    protected function compactParent(Category $category): ?array
    {
        if (! $category->relationLoaded('parent')) {
            return null;
        }

        $parent = $category->getRelation('parent');

        if (! $parent instanceof Category) {
            return null;
        }

        return [
            'id' => $parent->id,
            'name' => $parent->name,
        ];
    }

    /**
     * Return the assigned departments only when the relation is loaded.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function compactDepartments(Category $category): ?array
    {
        if (! $category->relationLoaded('departments')) {
            return null;
        }

        return $category->getRelation('departments')
            ->map(fn ($department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => (bool) $department->is_active,
            ])
            ->values()
            ->all();
    }

    /**
     * Return nested children only when the relation is loaded.
     *
     * @return array<int, mixed>|null
     */
    protected function compactChildren(Category $category): ?array
    {
        if (! $category->relationLoaded('children')) {
            return null;
        }

        return self::collection($category->getRelation('children'))->resolve();
    }
}
