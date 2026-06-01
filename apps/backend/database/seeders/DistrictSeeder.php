<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Seed stable district reference data.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Centrum', 'postal_prefix' => '1011', 'center_lat' => 52.37380000, 'center_lng' => 4.89390000, 'radius_meters' => 1800],
            ['name' => 'Noord', 'postal_prefix' => '1021', 'center_lat' => 52.40090000, 'center_lng' => 4.91540000, 'radius_meters' => 2500],
            ['name' => 'Oost', 'postal_prefix' => '1091', 'center_lat' => 52.35670000, 'center_lng' => 4.93070000, 'radius_meters' => 2200],
            ['name' => 'Zuid', 'postal_prefix' => '1071', 'center_lat' => 52.34280000, 'center_lng' => 4.87560000, 'radius_meters' => 2300],
            ['name' => 'West', 'postal_prefix' => '1051', 'center_lat' => 52.37850000, 'center_lng' => 4.85490000, 'radius_meters' => 2400],
        ])->each(fn (array $district): District => District::updateOrCreate(
            ['name' => $district['name']],
            $district + ['is_active' => true, 'created_at' => now()]
        ));
    }
}
