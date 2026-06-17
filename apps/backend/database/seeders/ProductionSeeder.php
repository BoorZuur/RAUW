<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds production reference data plus one operational officer and one
 * example issue. Does not run local demo accounts.
 *
 * Requires SEED_OFFICER_PASSWORD and SEED_REPORTER_PASSWORD in the environment.
 *
 * @see ProductionOfficerSeeder
 * @see ProductionExampleIssueSeeder
 */
class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            HubSeeder::class,
            DistrictSeeder::class,
            CategorySeeder::class,
            BlockedKeywordSeeder::class,
            ProductionOfficerSeeder::class,
            ProductionExampleIssueSeeder::class,
        ]);
    }
}
