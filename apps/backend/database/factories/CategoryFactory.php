<?php

namespace Database\Factories;

use App\Enums\Department;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'department' => fake()->randomElement(Department::cases()),
            'weight' => fake()->numberBetween(1, 10),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
