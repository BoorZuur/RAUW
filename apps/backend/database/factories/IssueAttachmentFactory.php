<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueAttachment>
 */
class IssueAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'file_path' => 'issue-attachments/'.fake()->uuid().'.jpg',
            'file_url' => fake()->imageUrl(),
            'original_name' => fake()->word().'.jpg',
            'file_type' => fake()->randomElement(['image/jpeg', 'image/png', 'application/pdf']),
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_at' => now(),
        ];
    }
}
