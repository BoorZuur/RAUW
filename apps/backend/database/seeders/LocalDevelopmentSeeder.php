<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalDevelopmentSeeder extends Seeder
{
    /**
     * Seed deterministic local demo data.
     *
     * Auth demo accounts are delegated to AuthDemoAccountsSeeder so the same
     * hardcoded credentials are produced whether this seeder runs or the
     * dedicated auth seeder is called directly.
     */
    public function run(): void
    {
        $this->call(AuthDemoAccountsSeeder::class);
    }
}
