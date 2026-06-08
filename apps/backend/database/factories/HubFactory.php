<?php

namespace Database\Factories;

use App\Models\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hub>
 */
class HubFactory extends Factory
{
    /**
     * Rotterdam cluster defaults for factory-generated hubs.
     *
     * @var array<int, array{name: string, address: string, postal_code: string, latitude: float, longitude: float}>
     */
    private const ROTTERDAM_CLUSTERS = [
        [
            'name' => 'Cluster Centrum',
            'address' => 'Zalmstraat 7',
            'postal_code' => '3016 DS',
            'latitude' => 51.9106846,
            'longitude' => 4.4814932,
        ],
        [
            'name' => 'Cluster Noord',
            'address' => 'Oostmaaslaan 53-71',
            'postal_code' => '3063 AN',
            'latitude' => 51.9176497,
            'longitude' => 4.5151947,
        ],
        [
            'name' => 'Cluster Zuid',
            'address' => 'Molenvliet 6',
            'postal_code' => '3076 CK',
            'latitude' => 51.8857391,
            'longitude' => 4.5232258,
        ],
        [
            'name' => 'Cluster Buitengebieden',
            'address' => 'Steenhouwerstraat 72',
            'postal_code' => '3194 AG',
            'latitude' => 51.8729038,
            'longitude' => 4.3761305,
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cluster = fake()->randomElement(self::ROTTERDAM_CLUSTERS);

        return [
            'name' => $cluster['name'].' '.fake()->unique()->numberBetween(1000, 9999),
            'address' => $cluster['address'],
            'postal_code' => $cluster['postal_code'],
            'latitude' => $cluster['latitude'],
            'longitude' => $cluster['longitude'],
        ];
    }
}
