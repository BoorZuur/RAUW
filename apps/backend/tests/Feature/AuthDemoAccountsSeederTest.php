<?php

namespace Tests\Feature;

use App\Enums\Department;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Database\Seeders\AuthDemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthDemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_account_per_actor_type(): void
    {
        District::factory()->create(['name' => 'Centrum']);

        $this->seed(AuthDemoAccountsSeeder::class);

        $this->assertSame(1, User::where('email', 'demo.user@example.com')->count());
        $this->assertSame(1, Officer::where('email', 'demo.officer@example.com')->count());
        $this->assertSame(1, Manager::where('email', 'demo.manager@example.com')->count());
    }

    public function test_seeded_accounts_use_the_shared_demo_password(): void
    {
        District::factory()->create(['name' => 'Centrum']);

        $this->seed(AuthDemoAccountsSeeder::class);

        $user = User::where('email', 'demo.user@example.com')->firstOrFail();
        $officer = Officer::where('email', 'demo.officer@example.com')->firstOrFail();
        $manager = Manager::where('email', 'demo.manager@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue(Hash::check('password', $officer->password));
        $this->assertTrue(Hash::check('password', $manager->password));

        $this->assertTrue($user->is_active);
        $this->assertTrue($officer->is_active);
        $this->assertTrue($manager->is_active);

        $this->assertSame('BOA-DEMO', $officer->badge_number);
        $this->assertSame(Department::Both, $manager->department instanceof Department
            ? $manager->department
            : Department::from($manager->department));
    }

    public function test_seeder_is_idempotent(): void
    {
        District::factory()->create(['name' => 'Centrum']);

        $this->seed(AuthDemoAccountsSeeder::class);
        $this->seed(AuthDemoAccountsSeeder::class);

        $this->assertSame(1, User::where('email', 'demo.user@example.com')->count());
        $this->assertSame(1, Officer::where('email', 'demo.officer@example.com')->count());
        $this->assertSame(1, Manager::where('email', 'demo.manager@example.com')->count());
    }

    public function test_seeder_handles_missing_districts_gracefully(): void
    {
        // No districts seeded — officer/manager should still be created with
        // a null district reference.
        $this->seed(AuthDemoAccountsSeeder::class);

        $officer = Officer::where('email', 'demo.officer@example.com')->firstOrFail();
        $manager = Manager::where('email', 'demo.manager@example.com')->firstOrFail();

        $this->assertNull($officer->district_id);
        $this->assertNull($manager->district_id);
    }
}
