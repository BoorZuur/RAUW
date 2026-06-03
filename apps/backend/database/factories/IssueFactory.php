<?php

namespace Database\Factories;

use App\Enums\ChatStatus;
use App\Enums\IssueStatus;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(IssueStatus::cases());

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->withDepartments(),
            'assigned_officer_id' => null,
            'district_id' => District::factory(),
            'duplicate_of_id' => null,
            'chat_closed_by_officer_id' => null,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'postal_code' => fake()->postcode(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(51.8, 52.5),
            'longitude' => fake()->longitude(4.0, 5.5),
            'status' => $status,
            'chat_status' => fake()->randomElement(ChatStatus::cases()),
            'priority' => fake()->optional()->numberBetween(1, 40),
            'duplicate_count' => 0,
            'participant_count' => 0,
            'vote_count' => 0,
            'is_flagged' => false,
            'visibility' => Visibility::Visible,
            'is_anonymous' => false,
            'anonymous_alias' => null,
            'resolved_at' => in_array($status, [IssueStatus::Resolved, IssueStatus::Closed], true) ? now() : null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Issue $issue): void {
            $category = $issue->category()->with('departments')->first();

            if ($category !== null) {
                $issue->syncDepartments($category->departmentIds());
            }
        });
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_officer_id' => Officer::factory(),
        ]);
    }
}
