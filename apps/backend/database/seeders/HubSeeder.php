<?php

namespace Database\Seeders;

use App\Models\Hub;
use Illuminate\Database\Seeder;

class HubSeeder extends Seeder
{
    /**
     * Seed stable Rotterdam cluster hub reference data.
     */
    public function run(): void
    {
        foreach (require __DIR__.'/data/hubs.php' as $hub) {
            Hub::updateOrCreate(
                ['name' => $hub['name']],
                [
                    'address' => $hub['address'],
                    'postal_code' => $hub['postal_code'],
                    'latitude' => $hub['latitude'],
                    'longitude' => $hub['longitude'],
                ],
            );
        }
    }
}
