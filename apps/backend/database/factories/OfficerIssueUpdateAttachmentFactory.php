<?php

namespace Database\Factories;

use App\Models\OfficerIssueUpdate;
use App\Models\OfficerIssueUpdateAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficerIssueUpdateAttachment>
 */
class OfficerIssueUpdateAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'officer_issue_update_id' => OfficerIssueUpdate::factory(),
            'file_path' => 'officer-issue-update-attachments/'.fake()->uuid().'.jpg',
            'file_url' => null,
            'original_name' => fake()->word().'.jpg',
            'file_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_at' => now(),
        ];
    }
}
