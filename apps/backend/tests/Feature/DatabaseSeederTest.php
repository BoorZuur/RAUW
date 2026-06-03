<?php

namespace Tests\Feature;

use App\Models\BlockedKeyword;
use App\Models\Category;
use App\Models\District;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_reference_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(5, District::count());
        $this->assertGreaterThanOrEqual(7, Category::count());
        $this->assertGreaterThanOrEqual(3, BlockedKeyword::count());
    }
}
