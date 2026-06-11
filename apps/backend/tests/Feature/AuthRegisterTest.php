<?php

namespace Tests\Feature;

use App\Enums\ActorType;
use App\Models\Department;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private Department $activeDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeDepartment = Department::factory()->create();
    }

    /**
     * Build a valid officer registration payload, allowing per-test overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'new-officer',
            'email' => 'new.officer@example.com',
            'password' => self::PASSWORD,
            'confirm_password' => self::PASSWORD,
            'badge_number' => 'BOA-1234',
            'department_ids' => [$this->activeDepartment->id],
            'latitude' => 51.9106846,
            'longitude' => 4.4814932,
        ], $overrides);
    }

    /**
     * Create an existing officer to assert uniqueness constraints against.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function existingOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'username' => 'existing-officer',
            'email' => 'existing.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-EXIST',
        ], $overrides));
    }

    /**
     * Build a valid user registration payload, allowing per-test overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'new-user',
            'email' => 'new.user@example.com',
            'password' => self::PASSWORD,
            'confirm_password' => self::PASSWORD,
        ], $overrides);
    }

    /**
     * Create an existing manager to assert cross-table uniqueness against.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function existingManager(array $overrides = []): Manager
    {
        return Manager::create(array_merge([
            'username' => 'existing-manager',
            'email' => 'existing.manager@example.com',
            'password' => self::PASSWORD,
        ], $overrides));
    }

    public function test_officer_can_register_and_receives_safe_profile(): void
    {
        $response = $this->postJson('/api/auth/register/officer', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'actor_type',
                'profile' => [
                    'id', 'username', 'email', 'badge_number',
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::Officer->value)
            ->assertJsonPath('profile.username', 'new-officer')
            ->assertJsonPath('profile.email', 'new.officer@example.com')
            ->assertJsonPath('profile.badge_number', 'BOA-1234');

        $json = $response->json();
        $this->assertNotEmpty($json['access_token']);
        // `actor_type` and access-control flags are not duplicated inside `profile`.
        $this->assertArrayNotHasKey('actor_type', $json['profile']);
        $this->assertArrayNotHasKey('is_active', $json['profile']);
        $this->assertArrayNotHasKey('password', $json['profile']);
        $this->assertArrayNotHasKey('remember_token', $json['profile']);
        $this->assertArrayNotHasKey('confirm_password', $json['profile']);
    }

    public function test_registration_persists_officer_record(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload())
            ->assertCreated();

        $this->assertDatabaseHas('officers', [
            'username' => 'new-officer',
            'email' => 'new.officer@example.com',
            'badge_number' => 'BOA-1234',
        ]);

        $officer = Officer::where('email', 'new.officer@example.com')->firstOrFail();
        $this->assertSame('new-officer', $officer->username);
        $this->assertSame('BOA-1234', $officer->badge_number);
        $this->assertTrue((bool) $officer->is_active);
    }

    public function test_registration_creates_officer_only(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload())
            ->assertCreated();

        $this->assertSame(1, Officer::query()->count());
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Manager::query()->count());
    }

    public function test_registration_stores_hashed_password(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload())
            ->assertCreated();

        $officer = Officer::where('email', 'new.officer@example.com')->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $officer->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $officer->password));
    }

    public function test_registration_token_authenticates_me_endpoint(): void
    {
        $response = $this->postJson('/api/auth/register/officer', $this->validPayload());

        $token = $response->json('access_token');
        $officerId = $response->json('profile.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::Officer->value)
            ->assertJsonPath('profile.id', $officerId)
            ->assertJsonPath('profile.email', 'new.officer@example.com')
            ->assertJsonPath('profile.badge_number', 'BOA-1234');
    }

    public function test_registration_issues_exactly_one_token(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload())
            ->assertCreated();

        $this->assertSame(1, PersonalAccessToken::query()->count());
    }

    public function test_registration_requires_username(): void
    {
        $payload = $this->validPayload();
        unset($payload['username']);

        $this->postJson('/api/auth/register/officer', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_requires_email(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $this->postJson('/api/auth/register/officer', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password(): void
    {
        $payload = $this->validPayload();
        unset($payload['password']);

        $this->postJson('/api/auth/register/officer', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_badge_number(): void
    {
        $payload = $this->validPayload();
        unset($payload['badge_number']);

        $this->postJson('/api/auth/register/officer', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['badge_number']);
    }

    public function test_registration_requires_matching_confirm_password(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'confirm_password' => 'does-not-match',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_password']);
    }

    public function test_registration_rejects_malformed_email(): void
    {
        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'email' => 'not-an-email',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_rejects_duplicate_username(): void
    {
        $existing = $this->existingOfficer();

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'username' => $existing->username,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $existing = $this->existingOfficer();

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'email' => $existing->email,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_rejects_duplicate_badge_number(): void
    {
        $existing = $this->existingOfficer();

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'badge_number' => $existing->badge_number,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['badge_number']);
    }

    public function test_registration_rejects_badge_number_of_soft_deleted_officer(): void
    {
        $existing = $this->existingOfficer();
        $existing->delete();

        $this->assertSoftDeleted('officers', ['id' => $existing->id]);

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'badge_number' => $existing->badge_number,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['badge_number']);
    }

    public function test_registration_ignores_actor_type_input(): void
    {
        $response = $this->postJson('/api/auth/register/officer', $this->validPayload([
            'actor_type' => 'user', // attempts to coerce a different actor; must be ignored
        ]));

        $response->assertCreated()
            ->assertJsonPath('actor_type', ActorType::Officer->value);

        $this->assertArrayNotHasKey('actor_type', $response->json('profile'));

        $this->assertSame(1, Officer::query()->count());
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Manager::query()->count());
    }

    public function test_officer_registration_rejects_email_already_used_by_user(): void
    {
        User::factory()->create(['email' => 'shared@example.com']);

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'email' => 'shared@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, Officer::query()->count());
    }

    public function test_officer_registration_rejects_email_already_used_by_manager(): void
    {
        $this->existingManager(['email' => 'shared@example.com']);

        $this->postJson('/api/auth/register/officer', $this->validPayload([
            'email' => 'shared@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, Officer::query()->count());
    }

    // ---------------------------------------------------------------------
    // Public user registration: POST /api/auth/register/user
    // ---------------------------------------------------------------------

    public function test_user_can_register_and_receives_safe_profile(): void
    {
        $response = $this->postJson('/api/auth/register/user', $this->validUserPayload());

        $response->assertCreated()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'actor_type',
                'profile' => [
                    'id', 'username', 'email',
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::User->value)
            ->assertJsonPath('profile.username', 'new-user')
            ->assertJsonPath('profile.email', 'new.user@example.com');

        $json = $response->json();
        $this->assertNotEmpty($json['access_token']);
        // `actor_type` and access-control flags are not duplicated inside `profile`.
        $this->assertArrayNotHasKey('actor_type', $json['profile']);
        $this->assertArrayNotHasKey('is_active', $json['profile']);
        $this->assertArrayNotHasKey('password', $json['profile']);
        $this->assertArrayNotHasKey('remember_token', $json['profile']);
        $this->assertArrayNotHasKey('confirm_password', $json['profile']);
        $this->assertArrayNotHasKey('flag_count', $json['profile']);
        $this->assertArrayNotHasKey('is_under_review', $json['profile']);
    }

    public function test_user_registration_persists_user_record(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload())
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'username' => 'new-user',
            'email' => 'new.user@example.com',
        ]);

        $user = User::where('email', 'new.user@example.com')->firstOrFail();
        $this->assertSame('new-user', $user->username);
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_user_registration_creates_user_only(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload())
            ->assertCreated();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, Officer::query()->count());
        $this->assertSame(0, Manager::query()->count());
    }

    public function test_user_registration_stores_hashed_password(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload())
            ->assertCreated();

        $user = User::where('email', 'new.user@example.com')->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_user_registration_applies_default_system_managed_flags(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload())
            ->assertCreated();

        $user = User::where('email', 'new.user@example.com')->firstOrFail();

        $this->assertTrue((bool) $user->is_active);
        $this->assertSame(0, (int) $user->flag_count);
        $this->assertFalse((bool) $user->is_under_review);
    }

    public function test_user_registration_ignores_system_managed_input(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'is_active' => false,
            'flag_count' => 99,
            'is_under_review' => true,
        ]))->assertCreated();

        $user = User::where('email', 'new.user@example.com')->firstOrFail();

        $this->assertTrue((bool) $user->is_active);
        $this->assertSame(0, (int) $user->flag_count);
        $this->assertFalse((bool) $user->is_under_review);
    }

    public function test_user_registration_token_authenticates_me_endpoint(): void
    {
        $response = $this->postJson('/api/auth/register/user', $this->validUserPayload());

        $token = $response->json('access_token');
        $userId = $response->json('profile.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::User->value)
            ->assertJsonPath('profile.id', $userId)
            ->assertJsonPath('profile.email', 'new.user@example.com')
            ->assertJsonPath('profile.username', 'new-user');
    }

    public function test_user_registration_issues_exactly_one_token(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload())
            ->assertCreated();

        $this->assertSame(1, PersonalAccessToken::query()->count());
    }

    public function test_user_registration_requires_username(): void
    {
        $payload = $this->validUserPayload();
        unset($payload['username']);

        $this->postJson('/api/auth/register/user', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_registration_requires_email(): void
    {
        $payload = $this->validUserPayload();
        unset($payload['email']);

        $this->postJson('/api/auth/register/user', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_requires_password(): void
    {
        $payload = $this->validUserPayload();
        unset($payload['password']);

        $this->postJson('/api/auth/register/user', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_registration_rejects_malformed_email(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'email' => 'not-an-email',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_rejects_short_password(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'password' => 'short',
            'confirm_password' => 'short',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_registration_requires_matching_confirm_password(): void
    {
        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'confirm_password' => 'does-not-match',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_password']);
    }

    public function test_user_registration_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'taken-username']);

        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'username' => 'taken-username',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->assertSame(1, User::query()->count());
    }

    public function test_user_registration_rejects_duplicate_email_in_users(): void
    {
        User::factory()->create(['email' => 'shared@example.com']);

        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'email' => 'shared@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(1, User::query()->count());
    }

    public function test_user_registration_rejects_email_already_used_by_officer(): void
    {
        $this->existingOfficer(['email' => 'shared@example.com']);

        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'email' => 'shared@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_user_registration_rejects_email_already_used_by_manager(): void
    {
        $this->existingManager(['email' => 'shared@example.com']);

        $this->postJson('/api/auth/register/user', $this->validUserPayload([
            'email' => 'shared@example.com',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, User::query()->count());
    }
}
