<?php

namespace Tests\Feature;

use App\Enums\Department;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_creates_valid_users_with_required_username(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->username);
        $this->assertModelExists($user);
    }

    public function test_officers_and_managers_have_remember_me_token_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('officers', 'remember_token'));
        $this->assertTrue(Schema::hasColumn('managers', 'remember_token'));
    }

    public function test_sanctum_personal_access_tokens_table_is_installed(): void
    {
        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'tokenable_type'));
        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'tokenable_id'));
        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'token'));
    }

    public function test_all_actor_models_can_issue_sanctum_tokens(): void
    {
        $this->assertContains(
            \Laravel\Sanctum\HasApiTokens::class,
            class_uses_recursive(User::class),
        );
        $this->assertContains(
            \Laravel\Sanctum\HasApiTokens::class,
            class_uses_recursive(Officer::class),
        );
        $this->assertContains(
            \Laravel\Sanctum\HasApiTokens::class,
            class_uses_recursive(Manager::class),
        );
    }

    public function test_user_moderation_fields_are_not_publicly_mass_assignable(): void
    {
        $user = new User();

        $this->assertContains('name', $user->getFillable());
        $this->assertContains('username', $user->getFillable());
        $this->assertContains('email', $user->getFillable());
        $this->assertContains('password', $user->getFillable());
        $this->assertNotContains('is_active', $user->getFillable());
        $this->assertNotContains('flag_count', $user->getFillable());
        $this->assertNotContains('is_under_review', $user->getFillable());
    }

    public function test_users_keep_email_and_username_reserved_after_soft_delete(): void
    {
        $deletedUser = User::factory()->create([
            'email' => 'deleted@example.com',
            'username' => 'deleted-user',
        ]);

        $deletedUser->delete();

        // Soft-deleted accounts retain unique identifiers. Reuse requires an explicit restore/reactivation flow.
        $this->expectException(QueryException::class);

        User::factory()->create([
            'email' => 'deleted@example.com',
            'username' => 'new-user',
        ]);
    }

    public function test_users_keep_username_reserved_after_soft_delete(): void
    {
        $deletedUser = User::factory()->create([
            'email' => 'deleted-username@example.com',
            'username' => 'reserved-user',
        ]);

        $deletedUser->delete();

        $this->expectException(QueryException::class);

        User::factory()->create([
            'email' => 'new-user@example.com',
            'username' => 'reserved-user',
        ]);
    }

    public function test_officers_keep_email_username_and_badge_number_reserved_after_soft_delete(): void
    {
        $deletedOfficer = Officer::create([
            'username' => 'deleted-officer',
            'email' => 'deleted.officer@example.com',
            'password' => 'password',
            'badge_number' => 'BADGE-001',
        ]);

        $deletedOfficer->delete();

        $this->assertUniqueConstraintStillApplies(fn () => Officer::create([
            'username' => 'new-officer-email',
            'email' => 'deleted.officer@example.com',
            'password' => 'password',
            'badge_number' => 'BADGE-002',
        ]));

        $this->assertUniqueConstraintStillApplies(fn () => Officer::create([
            'username' => 'deleted-officer',
            'email' => 'new.officer@example.com',
            'password' => 'password',
            'badge_number' => 'BADGE-003',
        ]));

        $this->assertUniqueConstraintStillApplies(fn () => Officer::create([
            'username' => 'new-officer-badge',
            'email' => 'new.badge@example.com',
            'password' => 'password',
            'badge_number' => 'BADGE-001',
        ]));
    }

    public function test_managers_keep_email_and_username_reserved_after_soft_delete(): void
    {
        $deletedManager = Manager::create([
            'username' => 'deleted-manager',
            'email' => 'deleted.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $deletedManager->delete();

        $this->assertUniqueConstraintStillApplies(fn () => Manager::create([
            'username' => 'new-manager-email',
            'email' => 'deleted.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]));

        $this->assertUniqueConstraintStillApplies(fn () => Manager::create([
            'username' => 'deleted-manager',
            'email' => 'new.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]));
    }

    public function test_manager_uses_standard_laravel_timestamps(): void
    {
        $manager = Manager::create([
            'username' => 'timestamp-manager',
            'email' => 'timestamp.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $this->assertNotNull($manager->created_at);
        $this->assertNotNull($manager->updated_at);
    }

    public function test_managers_table_has_main_manager_fields(): void
    {
        $this->assertTrue(Schema::hasColumn('managers', 'is_main_manager'));
        $this->assertTrue(Schema::hasColumn('managers', 'created_by_manager_id'));
    }

    public function test_manager_main_manager_fields_default_to_safe_values(): void
    {
        $manager = Manager::create([
            'username' => 'default-flags-manager',
            'email' => 'default.flags.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $manager->refresh();

        $this->assertFalse($manager->is_main_manager);
        $this->assertNull($manager->created_by_manager_id);
    }

    public function test_manager_main_manager_fields_are_cast(): void
    {
        $manager = Manager::create([
            'username' => 'cast-flags-manager',
            'email' => 'cast.flags.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $manager->refresh();

        $this->assertIsBool($manager->is_main_manager);
    }

    public function test_main_manager_field_is_not_publicly_mass_assignable(): void
    {
        $manager = new Manager();

        $this->assertNotContains('is_main_manager', $manager->getFillable());
        $this->assertContains('created_by_manager_id', $manager->getFillable());
    }

    public function test_manager_self_referential_creator_relationship(): void
    {
        $creator = Manager::create([
            'username' => 'creator-manager',
            'email' => 'creator.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
        ]);

        $created = Manager::create([
            'username' => 'subordinate-manager',
            'email' => 'subordinate.manager@example.com',
            'password' => 'password',
            'department' => Department::Both,
            'created_by_manager_id' => $creator->id,
        ]);

        $this->assertTrue($created->creator->is($creator));
        $this->assertTrue($creator->createdManagers->contains($created));
    }

    private function assertUniqueConstraintStillApplies(callable $callback): void
    {
        try {
            $callback();

            $this->fail('Expected a unique constraint violation after soft delete.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}
