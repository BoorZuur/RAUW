<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Hub;
use App\Models\Officer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds one production officer assigned to Cluster Centrum and the Centrum wijk.
 *
 * Credentials come from the environment; passwords are never hardcoded.
 */
class ProductionOfficerSeeder extends Seeder
{
    private const DEFAULT_EMAIL = 'officer@example.com';

    private const DEFAULT_USERNAME = 'officer';

    private const DEFAULT_BADGE_NUMBER = 'BOA-001';

    public function run(): void
    {
        $hub = Hub::query()
            ->where('name', 'Cluster Centrum')
            ->first();

        $district = District::query()
            ->where('name', 'Centrum')
            ->first();

        if ($hub === null) {
            throw new \RuntimeException('Cluster Centrum hub not found. Run HubSeeder first.');
        }

        if ($district === null) {
            throw new \RuntimeException('Centrum district not found. Run DistrictSeeder first.');
        }

        if ($district->hub_id !== $hub->id) {
            throw new \RuntimeException('Centrum district must belong to Cluster Centrum hub.');
        }

        $department = Department::query()
            ->where('code', 'boa_jeugd')
            ->first();

        if ($department === null) {
            throw new \RuntimeException('BOA / Jeugd department not found. Run CategorySeeder first.');
        }

        $email = (string) env('SEED_OFFICER_EMAIL', self::DEFAULT_EMAIL);
        $username = (string) env('SEED_OFFICER_USERNAME', self::DEFAULT_USERNAME);
        $badgeNumber = (string) env('SEED_OFFICER_BADGE_NUMBER', self::DEFAULT_BADGE_NUMBER);
        $password = $this->requireEnvPassword('SEED_OFFICER_PASSWORD');

        $officer = Officer::updateOrCreate(
            ['email' => $email],
            [
                'username' => $username,
                'password' => $password,
                'badge_number' => $badgeNumber,
                'hub_id' => $hub->id,
                'is_active' => true,
            ],
        );

        $officer->departments()->sync([$department->id]);
        $this->syncDistrict('district_officer', 'officer_id', $officer->id, $district->id);
    }

    private function requireEnvPassword(string $key): string
    {
        $password = (string) env($key, '');

        if ($password === '') {
            throw new \RuntimeException("{$key} must be set when running ProductionSeeder.");
        }

        return $password;
    }

    private function syncDistrict(
        string $pivotTable,
        string $actorKey,
        int $actorId,
        int $districtId,
    ): void {
        DB::table($pivotTable)->where($actorKey, $actorId)->delete();

        DB::table($pivotTable)->insertOrIgnore([
            $actorKey => $actorId,
            'district_id' => $districtId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
