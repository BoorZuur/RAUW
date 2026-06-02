<?php

namespace Tests\Feature;

use App\Enums\ActorType;
use App\Enums\Department;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ManagerCreationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * Create the local main manager who is authorized to create managers.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function mainManager(array $overrides = []): Manager
    {
        $manager = Manager::create(array_merge([
            'username' => 'main-manager',
            'email' => 'main.manager@example.com',
            'password' => self::PASSWORD,
            'department' => Department::Both,
        ], $overrides));

        $manager->forceFill(['is_main_manager' => true])->save();

        return $manager->refresh();
    }

    /**
     * Issue a Sanctum bearer token for the given actor.
     */
    private function tokenFor($actor): string
    {
        return $actor->createToken('test-token')->plainTextToken;
    }

    /**
     * Build a valid manager creation payload, allowing per-test overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'created-manager',
            'email' => 'created.manager@example.com',
            'password' => self::PASSWORD,
            'confirm_password' => self::PASSWORD,
            'department' => Department::DistrictManagement->value,
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // Successful creation
    // ---------------------------------------------------------------------

    public function test_main_manager_can_create_manager_with_safe_response(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure([
                'actor_type', 'id', 'username', 'email', 'department',
                'district_id', 'is_active', 'is_main_manager',
                'created_by_manager_id',
            ])
            ->assertJsonPath('actor_type', ActorType::Manager->value)
            ->assertJsonPath('username', 'created-manager')
            ->assertJsonPath('email', 'created.manager@example.com')
            ->assertJsonPath('department', Department::DistrictManagement->value)
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('is_main_manager', false)
            ->assertJsonPath('created_by_manager_id', $creator->id);

        $json = $response->json();
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('remember_token', $json);
        $this->assertArrayNotHasKey('access_token', $json);
        $this->assertArrayNotHasKey('token', $json);
        $this->assertArrayNotHasKey('confirm_password', $json);
    }

    public function test_created_manager_is_persisted_and_linked_to_creator(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertCreated();

        $this->assertDatabaseHas('managers', [
            'username' => 'created-manager',
            'email' => 'created.manager@example.com',
            'is_main_manager' => false,
            'created_by_manager_id' => $creator->id,
        ]);

        $manager = Manager::where('email', 'created.manager@example.com')->firstOrFail();
        $this->assertTrue((bool) $manager->is_active);
        $this->assertFalse((bool) $manager->is_main_manager);
        $this->assertSame($creator->id, $manager->created_by_manager_id);
    }

    public function test_created_manager_stores_hashed_password(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertCreated();

        $manager = Manager::where('email', 'created.manager@example.com')->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $manager->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $manager->password));
    }

    public function test_creation_can_link_a_district(): void
    {
        $district = District::factory()->create();
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'district_id' => $district->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('district_id', $district->id)
            ->assertJsonPath('district.id', $district->id);
    }

    public function test_creation_does_not_issue_a_token_for_the_new_manager(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        // Exactly one token exists already: the creator's bearer token.
        $this->assertSame(1, PersonalAccessToken::query()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload());

        $response->assertCreated();

        // No new token was minted for the created manager.
        $this->assertSame(1, PersonalAccessToken::query()->count());

        $manager = Manager::where('email', 'created.manager@example.com')->firstOrFail();
        $this->assertSame(0, $manager->tokens()->count());
    }

    public function test_main_manager_creates_only_a_manager_record(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertCreated();

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Officer::query()->count());
        // The creator plus the newly created manager.
        $this->assertSame(2, Manager::query()->count());
    }

    // ---------------------------------------------------------------------
    // Authorization
    // ---------------------------------------------------------------------

    public function test_creation_requires_authentication(): void
    {
        $this->postJson('/api/managers', $this->validPayload())
            ->assertUnauthorized();

        $this->assertSame(0, Manager::query()->count());
    }

    public function test_user_token_cannot_create_manager(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, Manager::query()->count());
    }

    public function test_officer_token_cannot_create_manager(): void
    {
        $officer = Officer::create([
            'username' => 'auth-officer',
            'email' => 'auth.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-AUTH',
        ]);
        $token = $this->tokenFor($officer);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, Manager::query()->count());
    }

    public function test_non_main_manager_token_cannot_create_manager(): void
    {
        $manager = Manager::create([
            'username' => 'regular-manager',
            'email' => 'regular.manager@example.com',
            'password' => self::PASSWORD,
            'department' => Department::Both,
        ]);
        $token = $this->tokenFor($manager);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertForbidden();

        // Only the non-main manager exists; no manager was created.
        $this->assertSame(1, Manager::query()->count());
    }

    public function test_inactive_main_manager_token_cannot_create_manager(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $creator->forceFill(['is_active' => false])->save();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertForbidden();

        $this->assertSame(1, Manager::query()->count());
    }

    // ---------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------

    public function test_creation_rejects_duplicate_manager_username(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        Manager::create([
            'username' => 'taken-manager',
            'email' => 'taken.manager@example.com',
            'password' => self::PASSWORD,
            'department' => Department::Both,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'username' => 'taken-manager',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_creation_rejects_email_already_used_by_user(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        User::factory()->create(['email' => 'shared@example.com']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'email' => 'shared@example.com',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_creation_rejects_email_already_used_by_officer(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        Officer::create([
            'username' => 'shared-officer',
            'email' => 'shared@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-SHARE',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'email' => 'shared@example.com',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_creation_rejects_email_already_used_by_manager(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'email' => $creator->email,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_creation_rejects_invalid_department(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'department' => 'not-a-department',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['department']);
    }

    public function test_creation_rejects_nonexistent_district(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'district_id' => 999999,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['district_id']);
    }

    public function test_creation_rejects_short_password(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'password' => 'short',
                'confirm_password' => 'short',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_creation_rejects_mismatched_confirm_password(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload([
                'confirm_password' => 'does-not-match',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_password']);
    }

    // ---------------------------------------------------------------------
    // Created manager can subsequently authenticate
    // ---------------------------------------------------------------------

    public function test_created_manager_can_login_with_submitted_credentials(): void
    {
        $creator = $this->mainManager();
        $token = $this->tokenFor($creator);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/managers', $this->validPayload())
            ->assertCreated();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'created.manager@example.com',
            'password' => self::PASSWORD,
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::Manager->value)
            ->assertJsonPath('profile.actor_type', ActorType::Manager->value)
            ->assertJsonPath('profile.email', 'created.manager@example.com')
            ->assertJsonPath('profile.is_main_manager', false);

        $this->assertNotEmpty($loginResponse->json('access_token'));
    }
}
