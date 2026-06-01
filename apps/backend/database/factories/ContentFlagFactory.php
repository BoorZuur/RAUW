<?php

namespace Database\Factories;

use App\Enums\FlagAction;
use App\Enums\FlagReason;
use App\Enums\FlagSource;
use App\Models\ContentFlag;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentFlag>
 */
class ContentFlagFactory extends Factory
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
            'comment_id' => null,
            'message_id' => null,
            'flagged_by_officer_id' => null,
            'reviewed_by_manager_id' => null,
            'flag_source' => fake()->randomElement(FlagSource::cases()),
            'flag_reason' => fake()->randomElement(FlagReason::cases()),
            'matched_keyword' => fake()->optional()->word(),
            'counts_toward_review' => true,
            'action_taken' => FlagAction::Pending,
            'flagged_at' => now(),
        ];
    }
}
