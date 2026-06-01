<?php

namespace Database\Factories;

use App\Enums\ActorType;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_type' => fake()->randomElement(ActorType::values()),
            'actor_id' => fake()->numberBetween(1, 1000),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'reviewed']),
            'target_table' => fake()->randomElement(['issues', 'users', 'content_flags']),
            'target_id' => fake()->numberBetween(1, 1000),
            'details' => json_encode(['source' => 'factory']),
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
