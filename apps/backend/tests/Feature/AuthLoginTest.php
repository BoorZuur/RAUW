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

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'login.user@example.com',
            'username' => 'login-user',
            'password' => Hash::make(self::PASSWORD),
        ], $overrides));
    }

    private function makeOfficer(array $overrides = []): Officer
    {
        $district = District::factory()->create();

        return Officer::create(array_merge([
            'username' => 'login-officer',
            'email' => 'login.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-LOGIN',
            'district_id' => $district->id,
        ], $overrides));
    }

    private function makeManager(array $overrides = []): Manager
    {
        $district = District::factory()->create();

        return Manager::create(array_merge([
            'username' => 'login-manager',
            'email' => 'login.manager@example.com',
            'password' => self::PASSWORD,
            'department' => Department::Both,
            'district_id' => $district->id,
        ], $overrides));
    }

    public function test_user_can_login_and_receives_safe_profile(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'actor_type',
                'profile' => [
                    'id', 'name', 'username', 'email',
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::User->value)
            ->assertJsonPath('profile.email', $user->email);

        $json = $response->json();
        $this->assertNotEmpty($json['access_token']);
        // `actor_type` lives on the top-level wrapper only; it is intentionally
        // not duplicated inside `profile`.
        $this->assertArrayNotHasKey('actor_type', $json['profile']);
        $this->assertArrayNotHasKey('password', $json['profile']);
        $this->assertArrayNotHasKey('remember_token', $json['profile']);
        $this->assertArrayNotHasKey('is_active', $json['profile']);
        $this->assertArrayNotHasKey('flag_count', $json['profile']);
        $this->assertArrayNotHasKey('is_under_review', $json['profile']);
    }

    public function test_officer_can_login_and_receives_safe_profile(): void
    {
        $officer = $this->makeOfficer();

        $response = $this->postJson('/api/auth/login', [
            'email' => $officer->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::Officer->value)
            ->assertJsonPath('profile.badge_number', 'BOA-LOGIN')
            ->assertJsonPath('profile.district.id', $officer->district_id);

        $profile = $response->json('profile');
        $this->assertArrayNotHasKey('actor_type', $profile);
        $this->assertArrayNotHasKey('password', $profile);
        $this->assertArrayNotHasKey('remember_token', $profile);
    }

    public function test_manager_can_login_and_receives_safe_profile(): void
    {
        $manager = $this->makeManager();

        $response = $this->postJson('/api/auth/login', [
            'email' => $manager->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('actor_type', ActorType::Manager->value)
            ->assertJsonPath('profile.department', Department::Both->value)
            ->assertJsonPath('profile.district.id', $manager->district_id);

        $profile = $response->json('profile');
        $this->assertArrayNotHasKey('actor_type', $profile);
        $this->assertArrayNotHasKey('password', $profile);
        $this->assertArrayNotHasKey('remember_token', $profile);
    }

    public function test_login_does_not_require_actor_type_input(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'actor_type' => 'officer', // ignored by the shared endpoint
        ]);

        $response->assertOk()
            ->assertJsonPath('actor_type', ActorType::User->value);
    }

    public function test_login_returns_401_for_wrong_password(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'not-the-right-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_returns_401_for_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => self::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_login_returns_422_when_email_missing(): void
    {
        $this->postJson('/api/auth/login', [
            'password' => self::PASSWORD,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_422_when_password_missing(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'someone@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_returns_422_for_malformed_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => self::PASSWORD,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['is_active' => false])->save();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_inactive_officer_cannot_login(): void
    {
        $officer = $this->makeOfficer();
        $officer->forceFill(['is_active' => false])->save();

        $this->postJson('/api/auth/login', [
            'email' => $officer->email,
            'password' => self::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_inactive_manager_cannot_login(): void
    {
        $manager = $this->makeManager();
        $manager->forceFill(['is_active' => false])->save();

        $this->postJson('/api/auth/login', [
            'email' => $manager->email,
            'password' => self::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_me_endpoint_returns_profile_with_valid_token(): void
    {
        $user = $this->makeUser();

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::User->value)
            ->assertJsonPath('profile.email', $user->email)
            ->assertJsonPath('profile.id', $user->id);
    }

    public function test_me_endpoint_returns_401_without_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_endpoint_returns_401_with_revoked_token(): void
    {
        $user = $this->makeUser();

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // Laravel's testing harness reuses the application instance across
        // requests, so we must flush cached guard resolutions to ensure the
        // next request re-authenticates the bearer token from scratch.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = $this->makeUser();

        $firstToken = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $secondToken = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $this->assertSame(2, PersonalAccessToken::query()->count());

        $this->withHeader('Authorization', 'Bearer ' . $firstToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        // Only the first token is revoked; the second still authenticates.
        $this->assertSame(1, PersonalAccessToken::query()->count());

        // Reset cached guard resolutions between requests (see note above).
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $firstToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $secondToken)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_logout_returns_401_without_token(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_officer_token_authenticates_me_endpoint(): void
    {
        $officer = $this->makeOfficer();

        $token = $this->postJson('/api/auth/login', [
            'email' => $officer->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::Officer->value)
            ->assertJsonPath('profile.badge_number', 'BOA-LOGIN');
    }

    public function test_manager_token_authenticates_me_endpoint(): void
    {
        $manager = $this->makeManager();

        $token = $this->postJson('/api/auth/login', [
            'email' => $manager->email,
            'password' => self::PASSWORD,
        ])->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::Manager->value)
            ->assertJsonPath('profile.department', Department::Both->value);
    }
}
