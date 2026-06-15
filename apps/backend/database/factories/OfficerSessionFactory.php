<?php

namespace Database\Factories;

use App\Models\Officer;
use App\Models\OfficerSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OfficerSession>
 */
class OfficerSessionFactory extends Factory
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
            'personal_access_token_id' => null,
            'hub_id' => null,
            'shift_start' => now()->subHours(2),
            'shift_end' => null,
            'start_lat' => fake()->latitude(51.8, 52.5),
            'start_lng' => fake()->longitude(4.0, 5.5),
            'distance_meters_at_login' => fake()->numberBetween(150, 5000),
            'is_hub_active' => false,
            'hub_active_until' => null,
            'last_lat' => fake()->latitude(51.8, 52.5),
            'last_lng' => fake()->longitude(4.0, 5.5),
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }

    /**
     * Sync hub_id from the officer when the session row was created without one.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (OfficerSession $session): void {
            if ($session->hub_id !== null) {
                return;
            }

            $session->loadMissing('officer');

            if ($session->officer?->hub_id !== null) {
                $session->forceFill(['hub_id' => $session->officer->hub_id])->save();
            }
        });
    }

    /**
     * Hub-radius login audit shape with optional hub-active expiry.
     */
    public function hubActive(?Carbon $until = null): static
    {
        return $this
            ->state(fn () => [
                'is_hub_active' => true,
                'distance_meters_at_login' => fake()->numberBetween(0, 80),
                'hub_active_until' => $until ?? now()->addHours(config('officer.hub_active_ttl_hours')),
            ])
            ->afterCreating(function (OfficerSession $session): void {
                if ($session->personal_access_token_id !== null) {
                    return;
                }

                $session->loadMissing('officer');

                $tokenResult = $session->officer->createToken('factory-session', ['hub-active']);

                $session->forceFill([
                    'personal_access_token_id' => $tokenResult->accessToken->id,
                ])->save();
            });
    }

    /**
     * Explicit remote-login audit defaults (outside hub radius).
     */
    public function remote(): static
    {
        return $this->state(fn () => [
            'personal_access_token_id' => null,
            'hub_id' => null,
            'distance_meters_at_login' => fake()->numberBetween(150, 5000),
            'is_hub_active' => false,
            'hub_active_until' => null,
        ]);
    }

    /**
     * Closed session for per-token logout or supersede scenarios.
     */
    public function closed(): static
    {
        return $this->state(fn () => [
            'shift_end' => now(),
            'is_active' => false,
        ]);
    }
}
