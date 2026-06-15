<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Hub;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Seed Rotterdam wijk district reference data.
     */
    public function run(): void
    {
        $hubIds = Hub::query()->pluck('id', 'name');

        foreach (require __DIR__.'/data/rotterdam_districts.php' as $district) {
            $hubId = $hubIds[$district['hub']] ?? null;

            if ($hubId === null) {
                throw new \RuntimeException("Hub not found for district [{$district['name']}]: {$district['hub']}");
            }

            District::updateOrCreate(
                ['name' => $district['name']],
                [
                    'hub_id' => $hubId,
                    'postal_prefix' => $district['postal_prefix'],
                    'center_lat' => $district['center_lat'],
                    'center_lng' => $district['center_lng'],
                    'radius_meters' => $district['radius_meters'],
                    'is_active' => true,
                ],
            );
        }
    }
}
