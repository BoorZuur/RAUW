<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A main category by default: `parent_id` is null and the general
     * `priority` orders it (lower number = higher priority). Subcategories use
     * `weight` for ordering instead.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'weight' => null,
            'priority' => fake()->numberBetween(1, 10),
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Configure the category as a subcategory of the given main category.
     */
    public function subcategoryOf(Category $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'priority' => null,
            'weight' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Attach the category to one or more departments through the pivot table.
     */
    public function withDepartments(Department ...$departments): static
    {
        $departments = $departments === []
            ? [Department::factory()]
            : $departments;

        return $this->hasAttached(collect($departments), [], 'departments');
    }
}
