<?php

namespace Database\Seeders;

use App\Enums\Department;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Seeder;

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

    public function run(): void
    {
        // Pick a deterministic district (the first seeded one by name) so
        // officer/manager profiles always reference the same district across
        // re-seeds. Falls back to null when no districts exist yet.
        $district = District::query()
            ->where('name', 'Centrum')
            ->first()
            ?? District::query()->orderBy('id')->first();

        User::updateOrCreate(
            ['email' => 'demo.user@example.com'],
            [
                'name' => 'Demo User',
                'username' => 'demo.user',
                'password' => self::DEMO_PASSWORD,
                'is_active' => true,
            ],
        );

        Officer::updateOrCreate(
            ['email' => 'demo.officer@example.com'],
            [
                'username' => 'demo.officer',
                'password' => self::DEMO_PASSWORD,
                'badge_number' => 'BOA-DEMO',
                'district_id' => $district?->id,
                'is_active' => true,
            ],
        );

        $manager = Manager::updateOrCreate(
            ['email' => 'demo.manager@example.com'],
            [
                'username' => 'demo.manager',
                'password' => self::DEMO_PASSWORD,
                'department' => Department::Both->value,
                'district_id' => $district?->id,
                'is_active' => true,
                'created_by_manager_id' => null,
            ],
        );

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
}
