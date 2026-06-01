<?php

namespace Database\Seeders;

use App\Enums\Department;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocalDevelopmentSeeder extends Seeder
{
    /**
     * Seed deterministic local demo accounts.
     */
    public function run(): void
    {
        $district = District::query()->first();

        User::updateOrCreate(['email' => 'demo.user@example.com'], [
            'name' => 'Demo User',
            'username' => 'demo.user',
            'password' => 'password',
        ]);

        Officer::updateOrCreate(['email' => 'demo.officer@example.com'], [
            'username' => 'demo.officer',
            'password' => 'password',
            'badge_number' => 'BOA-DEMO',
            'district_id' => $district?->id,
            'is_active' => true,
        ]);

        Manager::updateOrCreate(['email' => 'demo.manager@example.com'], [
            'username' => 'demo.manager',
            'password' => 'password',
            'department' => Department::Both->value,
            'district_id' => $district?->id,
            'is_active' => true,
        ]);
    }
}
