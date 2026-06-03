<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds exactly one hardcoded account per actor type (user, officer, manager)
 * so developers can exercise the shared login, profile, and logout endpoints
 * locally without relying on randomly generated fixtures.
 *
 * Credentials are intentionally predictable and MUST NOT be relied on outside
 * of local development environments.
 */
class AuthDemoAccountsSeeder extends Seeder
{
    /**
     * Deterministic password used for every demo account.
     */
    private const DEMO_PASSWORD = 'password';

    /**
     * Canonical department rows actors are assigned to, mirroring the
     * actor department migration.
     *
     * @var array<string, string>
     */
    private const CANONICAL_DEPARTMENTS = [
        'wijkbeheer' => 'Wijkbeheer',
        'boa_jeugd' => 'BOA / Jeugd',
    ];

    public function run(): void
    {
        // Pick a deterministic district (the first seeded one by name) so
        // officer/manager profiles always reference the same district across
        // re-seeds. Falls back to null when no districts exist yet.
        $district = District::query()
            ->where('name', 'Centrum')
            ->first()
            ?? District::query()->orderBy('id')->first();

        // Ensure the canonical department rows exist so demo actors can be
        // assigned real department records under the new schema.
        $departments = [];

        foreach (self::CANONICAL_DEPARTMENTS as $code => $name) {
            $departments[$code] = Department::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        User::updateOrCreate(
            ['email' => 'demo.user@example.com'],
            [
                'username' => 'demo.user',
                'password' => self::DEMO_PASSWORD,
                'is_active' => true,
            ],
        );

        $officer = Officer::updateOrCreate(
            ['email' => 'demo.officer@example.com'],
            [
                'username' => 'demo.officer',
                'password' => self::DEMO_PASSWORD,
                'badge_number' => 'BOA-DEMO',
                'is_active' => true,
            ],
        );

        // The demo officer belongs to both canonical departments so local
        // testing exercises the one-or-more department invariant.
        $officer->departments()->sync(
            collect($departments)->pluck('id')->all()
        );

        // Attach the deterministic demo district through the officer pivot so
        // re-seeds keep the same single district assignment without relying on
        // the removed officers.district_id column.
        $this->syncActorDistrict('district_officer', 'officer_id', $officer->id, $district?->id);

        $manager = Manager::updateOrCreate(
            ['email' => 'demo.manager@example.com'],
            [
                'username' => 'demo.manager',
                'password' => self::DEMO_PASSWORD,
                'is_active' => true,
                'created_by_manager_id' => null,
            ],
        );

        // The local demo manager is assigned to the Wijkbeheer department
        // through the pivot so local testing exercises the new many-to-many
        // manager department relationship.
        $manager->departments()->sync([$departments['wijkbeheer']->id]);

        // Attach the deterministic demo district through the manager pivot,
        // mirroring the officer assignment under the new many-to-many schema.
        $this->syncActorDistrict('district_manager', 'manager_id', $manager->id, $district?->id);

        // The local demo manager is the deterministic main manager used for
        // Postman testing. `is_main_manager` is intentionally NOT mass
        // assignable, so it is set directly here to bypass the model guard.
        //
        // In production the initial main manager MUST be provisioned through
        // trusted operational seeding or direct administration, never through
        // the public API.
        if (! $manager->is_main_manager) {
            $manager->is_main_manager = true;
            $manager->save();
        }
    }

    /**
     * Idempotently attach a single district to an actor through its pivot.
     *
     * Existing pivot rows for the actor are cleared first so re-seeds keep the
     * actor pinned to exactly the deterministic demo district. A null district
     * leaves the actor without any district assignment.
     */
    private function syncActorDistrict(
        string $pivotTable,
        string $actorKey,
        int $actorId,
        ?int $districtId,
    ): void {
        DB::table($pivotTable)->where($actorKey, $actorId)->delete();

        if ($districtId === null) {
            return;
        }

        DB::table($pivotTable)->insertOrIgnore([
            $actorKey => $actorId,
            'district_id' => $districtId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
