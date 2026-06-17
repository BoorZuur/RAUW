<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Officer;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('SEED_OFFICER_PASSWORD=production-officer-pass');
        $_ENV['SEED_OFFICER_PASSWORD'] = 'production-officer-pass';
        $_SERVER['SEED_OFFICER_PASSWORD'] = 'production-officer-pass';

        putenv('SEED_REPORTER_PASSWORD=production-reporter-pass');
        $_ENV['SEED_REPORTER_PASSWORD'] = 'production-reporter-pass';
        $_SERVER['SEED_REPORTER_PASSWORD'] = 'production-reporter-pass';
    }

    public function test_production_seeder_creates_officer_reporter_and_example_issue(): void
    {
        $this->seed(ProductionSeeder::class);

        $officer = Officer::query()->where('email', 'officer@example.com')->first();
        $reporter = User::query()->where('email', 'reporter@example.com')->first();
        $issue = Issue::query()->where('title', 'Scooter geparkeerd op het trottoir bij school')->first();

        $this->assertNotNull($officer);
        $this->assertNotNull($reporter);
        $this->assertNotNull($issue);
        $this->assertTrue(Hash::check('production-officer-pass', $officer->password));
        $this->assertTrue(Hash::check('production-reporter-pass', $reporter->password));
        $this->assertSame($reporter->id, $issue->user_id);
        $this->assertSame(1, $issue->participant_count);
        $this->assertCount(1, $issue->departments);
    }

    public function test_production_seeder_is_idempotent_for_example_issue(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame(
            1,
            Issue::query()->where('title', 'Scooter geparkeerd op het trottoir bij school')->count(),
        );
    }
}
