<?php

namespace Database\Factories;

use App\Models\BlockedKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedKeyword>
 */
class BlockedKeywordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keyword = fake()->unique()->words(2, true);

        return [
            'keyword' => $keyword,
            'keyword_normalized' => BlockedKeyword::normalizeKeyword($keyword),
            'created_at' => now(),
        ];
    }
}
