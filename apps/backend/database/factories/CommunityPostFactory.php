<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\CommunityPost;
use App\Models\District;
use App\Models\Officer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'officer_id' => Officer::factory(),
            'district_id' => District::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'visibility' => Visibility::Visible->value,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Hidden->value,
        ]);
    }

    public function orphaned(): static
    {
        return $this->state(fn (array $attributes) => [
            'district_id' => null,
        ]);
    }
}
