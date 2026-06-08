<?php

namespace App\Support;

use App\Models\Category;

class IssuePriorityResolver
{
    /**
     * Derive an issue's priority integer from its selected category.
     *
     * Uses the main category's `priority` value (lower number = higher urgency),
     * matching the same scale as `categories.priority`. Subcategories inherit
     * their parent main category's priority, not the subcategory `weight`.
     * Returns null when the main category has no priority set. Reserved for
     * future participant-based priority adjustments.
     */
    public static function fromCategory(Category $category): ?int
    {
        if ($category->parent_id !== null) {
            $category->loadMissing('parent');

            return $category->parent?->priority;
        }

        return $category->priority;
    }
}
