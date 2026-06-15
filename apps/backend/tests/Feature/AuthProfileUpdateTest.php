<?php

namespace Tests\Feature;

use App\Enums\ActorType;
use App\Models\District;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private const NEW_PASSWORD = 'new-secure-password';

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'profile.user@example.com',
            'username' => 'profile-user',
            'password' => Hash::make(self::PASSWORD),
        ], $overrides));
    }

    private function makeOfficer(array $overrides = []): Officer
    {
        $district = District::factory()->create();

        return Officer::create(array_merge([
            'username' => 'profile-officer',
            'email' => 'profile.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-PROFILE',
            'district_id' => $district->id,
        ], $overrides));
    }

    private function makeManager(array $overrides = []): Manager
    {
        return Manager::create(array_merge([
            'username' => 'profile-manager',
            'email' => 'profile.manager@example.com',
            'password' => self::PASSWORD,
        ], $overrides));
    }

    private function tokenAfterLogin(User|Officer|Manager $actor): string
    {
        return $this->postJson('/api/auth/login', [
            'email' => $actor->email,
            'password' => self::PASSWORD,
        ])->json('access_token');
    }

    private function patchProfile(string $token, array $payload)
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/auth/me', $payload);
    }

    public function test_inactive_user_can_change_username_and_password(): void
    {
        $user = $this->makeUser();
        $token = $this->tokenAfterLogin($user);
        $user->forceFill(['is_active' => false])->save();

        $this->patchProfile($token, [
            'username' => 'recovered-user',
            'password' => self::NEW_PASSWORD,
            'confirm_password' => self::NEW_PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('actor_type', ActorType::User->value)
            ->assertJsonPath('profile.username', 'recovered-user')
            ->assertJsonPath('profile.email', 'profile.user@example.com');

        $user->refresh();
        $this->assertSame('recovered-user', $user->username);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->password));
    }

    public function test_inactive_officer_cannot_change_email_or_badge_number(): void
    {
        $officer = $this->makeOfficer();
        $token = $this->tokenAfterLogin($officer);
        $officer->forceFill(['is_active' => false])->save();

        $this->patchProfile($token, [
            'email' => 'new.officer@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->patchProfile($token, [
            'badge_number' => 'BOA-NEW',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['badge_number']);

        $officer->refresh();
        $this->assertSame('profile.officer@example.com', $officer->email);
        $this->assertSame('BOA-PROFILE', $officer->badge_number);
    }

    public function test_inactive_manager_cannot_change_email(): void
    {
        $manager = $this->makeManager();
        $token = $this->tokenAfterLogin($manager);
        $manager->forceFill(['is_active' => false])->save();

        $this->patchProfile($token, [
            'email' => 'new.manager@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $manager->refresh();
        $this->assertSame('profile.manager@example.com', $manager->email);
    }

    public function test_active_user_can_change_email_username_and_password(): void
    {
        $user = $this->makeUser();
        $token = $this->tokenAfterLogin($user);

        $this->patchProfile($token, [
            'username' => 'active-user-new',
            'email' => 'active.new@example.com',
            'password' => self::NEW_PASSWORD,
            'confirm_password' => self::NEW_PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('profile.username', 'active-user-new')
            ->assertJsonPath('profile.email', 'active.new@example.com');

        $user->refresh();
        $this->assertSame('active-user-new', $user->username);
        $this->assertSame('active.new@example.com', $user->email);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->password));
    }

    public function test_active_officer_can_change_badge_number(): void
    {
        $officer = $this->makeOfficer();
        $token = $this->tokenAfterLogin($officer);

        $this->patchProfile($token, [
            'badge_number' => 'BOA-UPDATED',
        ])
            ->assertOk()
            ->assertJsonPath('profile.badge_number', 'BOA-UPDATED');

        $officer->refresh();
        $this->assertSame('BOA-UPDATED', $officer->badge_number);
    }

    public function test_active_user_cannot_change_badge_number(): void
    {
        $user = $this->makeUser();
        $token = $this->tokenAfterLogin($user);

        $this->patchProfile($token, [
            'badge_number' => 'SHOULD-FAIL',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['badge_number']);
    }
}
