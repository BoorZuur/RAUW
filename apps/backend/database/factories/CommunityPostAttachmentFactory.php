<?php

namespace Database\Factories;

use App\Models\CommunityPost;
use App\Models\CommunityPostAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPostAttachment>
 */
class CommunityPostAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_post_id' => CommunityPost::factory(),
            'file_path' => 'community-post-attachments/'.fake()->uuid().'.jpg',
            'file_url' => fake()->imageUrl(),
            'original_name' => fake()->word().'.jpg',
            'file_type' => fake()->randomElement(['image/jpeg', 'image/png', 'application/pdf']),
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_at' => now(),
        ];
    }
}
