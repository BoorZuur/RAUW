<?php

namespace Database\Factories;

use App\Models\IssueMessage;
use App\Models\IssueMessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueMessageAttachment>
 */
class IssueMessageAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_message_id' => IssueMessage::factory(),
            'file_path' => 'issue-message-attachments/'.fake()->uuid().'.jpg',
            'file_url' => null,
            'original_name' => fake()->word().'.jpg',
            'file_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_at' => now(),
        ];
    }
}
