<?php

namespace Tests\Feature;

use App\Enums\Department as DepartmentEnum;
use App\Models\Category;
use App\Models\Department;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * Create an active main manager who may manage departments.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function mainManager(array $overrides = []): Manager
    {
        $manager = Manager::create(array_merge([
            'username' => 'main-manager',
            'email' => 'main.manager@example.com',
            'password' => self::PASSWORD,
            'department' => DepartmentEnum::Both,
        ], $overrides));

        $manager->forceFill(['is_main_manager' => true])->save();

        return $manager->refresh();
    }

    /**
     * Create an active, ordinary (non-main) manager.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function regularManager(array $overrides = []): Manager
    {
        return Manager::create(array_merge([
            'username' => 'regular-manager',
            'email' => 'regular.manager@example.com',
            'password' => self::PASSWORD,
            'department' => DepartmentEnum::Both,
        ], $overrides));
    }

    /**
     * Issue a Sanctum bearer token for the given actor.
     */
    private function tokenFor($actor): string
    {
        return $actor->createToken('test-token')->plainTextToken;
    }

    /**
     * Build the request headers for an authenticated actor.
     *
     * @return array<string, string>
     */
    private function authHeaders($actor): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenFor($actor)];
    }

    // ---------------------------------------------------------------------
    // Main-manager CRUD
    // ---------------------------------------------------------------------

    public function test_main_manager_can_create_department(): void
    {
        $manager = $this->mainManager();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/departments', [
                'code' => 'sport',
                'name' => 'Sport Department',
            ])
            ->assertCreated()
            ->assertJsonPath('code', 'sport')
            ->assertJsonPath('name', 'Sport Department')
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('departments', [
            'code' => 'sport',
            'name' => 'Sport Department',
        ]);
    }

    public function test_main_manager_can_update_department(): void
    {
        $manager = $this->mainManager();
        $department = Department::factory()->create(['code' => 'old-code', 'name' => 'Old']);

        $this->withHeaders($this->authHeaders($manager))
            ->patchJson("/api/departments/{$department->id}", [
                'code' => 'new-code',
                'name' => 'New',
            ])
            ->assertOk()
            ->assertJsonPath('code', 'new-code')
            ->assertJsonPath('name', 'New');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'code' => 'new-code',
            'name' => 'New',
        ]);
    }

    public function test_main_manager_can_delete_department(): void
    {
        $manager = $this->mainManager();
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/departments/{$department->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_creation_rejects_duplicate_code(): void
    {
        $manager = $this->mainManager();
        Department::factory()->create(['code' => 'taken']);

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/departments', [
                'code' => 'taken',
                'name' => 'Duplicate',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    // ---------------------------------------------------------------------
    // Deleting a department cascades pivot but preserves categories
    // ---------------------------------------------------------------------

    public function test_deleting_department_removes_pivot_assignments_but_keeps_categories(): void
    {
        $manager = $this->mainManager();
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();

        $this->assertDatabaseHas('category_department', [
            'category_id' => $category->id,
            'department_id' => $department->id,
        ]);

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/departments/{$department->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('category_department', [
            'department_id' => $department->id,
        ]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    // ---------------------------------------------------------------------
    // Authorization: only active main managers may mutate departments
    // ---------------------------------------------------------------------

    public function test_department_mutation_requires_authentication(): void
    {
        $this->postJson('/api/departments', [
            'code' => 'unauth',
            'name' => 'Unauthenticated',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('departments', ['code' => 'unauth']);
    }

    public function test_non_main_manager_cannot_mutate_departments(): void
    {
        $manager = $this->regularManager();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/departments', [
                'code' => 'regular',
                'name' => 'Regular attempt',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'regular']);
    }

    public function test_inactive_main_manager_cannot_mutate_departments(): void
    {
        $manager = $this->mainManager();
        $manager->forceFill(['is_active' => false])->save();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/departments', [
                'code' => 'inactive',
                'name' => 'Inactive attempt',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'inactive']);
    }

    public function test_user_token_cannot_mutate_departments(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/departments', [
                'code' => 'user',
                'name' => 'User attempt',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'user']);
    }

    public function test_officer_token_cannot_mutate_departments(): void
    {
        $officer = Officer::create([
            'username' => 'dept-officer',
            'email' => 'dept.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-DEPT',
        ]);

        $this->withHeaders($this->authHeaders($officer))
            ->postJson('/api/departments', [
                'code' => 'officer',
                'name' => 'Officer attempt',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'officer']);
    }

    public function test_non_main_manager_cannot_delete_department(): void
    {
        $manager = $this->regularManager();
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/departments/{$department->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
