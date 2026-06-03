<?php

namespace Tests\Feature;

use App\Enums\Department as DepartmentEnum;
use App\Models\Category;
use App\Models\Department;
use App\Models\Issue;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * Create an active, ordinary (non-main) manager who may manage categories.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function activeManager(array $overrides = []): Manager
    {
        return Manager::create(array_merge([
            'username' => 'category-manager',
            'email' => 'category.manager@example.com',
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
    // Creation and department assignment
    // ---------------------------------------------------------------------

    public function test_active_manager_can_create_category_with_multiple_departments(): void
    {
        $manager = $this->activeManager();
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $response = $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Public space',
                'priority' => 3,
                'department_ids' => [$departmentA->id, $departmentB->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Public space')
            ->assertJsonPath('is_main_category', true)
            ->assertJsonPath('priority', 3)
            ->assertJsonPath('is_active', true);

        $departmentIds = collect($response->json('departments'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$departmentA->id, $departmentB->id], $departmentIds);

        $category = Category::firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$departmentA->id, $departmentB->id],
            $category->departments()->pluck('departments.id')->all()
        );
    }

    public function test_active_manager_can_update_category_and_resync_departments(): void
    {
        $manager = $this->activeManager();
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        $category = Category::factory()->withDepartments($departmentA)->create([
            'name' => 'Old name',
            'priority' => 5,
        ]);

        $response = $this->withHeaders($this->authHeaders($manager))
            ->patchJson("/api/categories/{$category->id}", [
                'name' => 'New name',
                'priority' => 1,
                'department_ids' => [$departmentB->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New name')
            ->assertJsonPath('priority', 1);

        $category->refresh();
        $this->assertSame('New name', $category->name);
        $this->assertSame(1, $category->priority);
        $this->assertEqualsCanonicalizing(
            [$departmentB->id],
            $category->departments()->pluck('departments.id')->all()
        );
    }

    // ---------------------------------------------------------------------
    // Hierarchy rules
    // ---------------------------------------------------------------------

    public function test_subcategory_can_reference_an_active_main_category(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $main = Category::factory()->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Subcategory',
                'parent_id' => $main->id,
                'weight' => 2,
                'department_ids' => [$department->id],
            ])
            ->assertCreated()
            ->assertJsonPath('is_main_category', false)
            ->assertJsonPath('parent_id', $main->id)
            ->assertJsonPath('weight', 2);
    }

    public function test_nested_subcategory_is_rejected(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $main = Category::factory()->withDepartments($department)->create();
        $sub = Category::factory()->subcategoryOf($main)->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Nested',
                'parent_id' => $sub->id,
                'weight' => 1,
                'department_ids' => [$department->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_main_category_rejects_a_weight_value(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Main with weight',
                'weight' => 4,
                'department_ids' => [$department->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['weight']);
    }

    public function test_subcategory_rejects_a_priority_value(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $main = Category::factory()->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Sub with priority',
                'parent_id' => $main->id,
                'priority' => 4,
                'department_ids' => [$department->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    // ---------------------------------------------------------------------
    // Priority / weight sorting (lower number = higher priority)
    // ---------------------------------------------------------------------

    public function test_main_categories_sort_by_ascending_priority(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();

        $low = Category::factory()->withDepartments($department)->create(['name' => 'Low priority', 'priority' => 10]);
        $high = Category::factory()->withDepartments($department)->create(['name' => 'High priority', 'priority' => 1]);
        $mid = Category::factory()->withDepartments($department)->create(['name' => 'Mid priority', 'priority' => 5]);

        $this->withHeaders($this->authHeaders($manager))
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $high->id)
            ->assertJsonPath('data.1.id', $mid->id)
            ->assertJsonPath('data.2.id', $low->id);
    }

    public function test_subcategories_sort_by_ascending_weight(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $main = Category::factory()->withDepartments($department)->create(['priority' => 1]);

        $heavier = Category::factory()->subcategoryOf($main)->withDepartments($department)->create(['name' => 'Heavier', 'weight' => 9]);
        $lighter = Category::factory()->subcategoryOf($main)->withDepartments($department)->create(['name' => 'Lighter', 'weight' => 2]);

        $this->withHeaders($this->authHeaders($manager))
            ->getJson("/api/categories/{$main->id}")
            ->assertOk()
            ->assertJsonPath('children.0.id', $lighter->id)
            ->assertJsonPath('children.1.id', $heavier->id);
    }

    // ---------------------------------------------------------------------
    // Disable (standard safe removal path)
    // ---------------------------------------------------------------------

    public function test_disabling_a_category_keeps_the_row_and_assignments(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->patchJson("/api/categories/{$category->id}/disable")
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('category_department', [
            'category_id' => $category->id,
            'department_id' => $department->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Hard delete (guarded)
    // ---------------------------------------------------------------------

    public function test_eligible_category_can_be_hard_deleted_and_pivot_cascades(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/categories/{$category->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('category_department', ['category_id' => $category->id]);
    }

    public function test_main_category_with_subcategories_cannot_be_hard_deleted(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $main = Category::factory()->withDepartments($department)->create();
        Category::factory()->subcategoryOf($main)->withDepartments($department)->create();

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/categories/{$main->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('categories', ['id' => $main->id]);
    }

    public function test_category_referenced_by_an_issue_returns_conflict_on_hard_delete(): void
    {
        $manager = $this->activeManager();
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();
        Issue::factory()->create(['category_id' => $category->id]);

        $this->withHeaders($this->authHeaders($manager))
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    // ---------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------

    public function test_creation_requires_at_least_one_department(): void
    {
        $manager = $this->activeManager();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'No departments',
                'priority' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['department_ids']);
    }

    public function test_creation_rejects_nonexistent_department(): void
    {
        $manager = $this->activeManager();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Bad department',
                'priority' => 1,
                'department_ids' => [999999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['department_ids.0']);
    }

    // ---------------------------------------------------------------------
    // Authorization
    // ---------------------------------------------------------------------

    public function test_category_management_requires_authentication(): void
    {
        $department = Department::factory()->create();

        $this->postJson('/api/categories', [
            'name' => 'Unauthenticated',
            'priority' => 1,
            'department_ids' => [$department->id],
        ])->assertUnauthorized();

        $this->assertSame(0, Category::query()->count());
    }

    public function test_user_token_cannot_manage_categories(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/categories', [
                'name' => 'User attempt',
                'priority' => 1,
                'department_ids' => [$department->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, Category::query()->count());
    }

    public function test_officer_token_cannot_manage_categories(): void
    {
        $officer = Officer::create([
            'username' => 'category-officer',
            'email' => 'category.officer@example.com',
            'password' => self::PASSWORD,
            'badge_number' => 'BOA-CAT',
        ]);
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($officer))
            ->postJson('/api/categories', [
                'name' => 'Officer attempt',
                'priority' => 1,
                'department_ids' => [$department->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, Category::query()->count());
    }

    public function test_inactive_manager_cannot_manage_categories(): void
    {
        $manager = $this->activeManager();
        $manager->forceFill(['is_active' => false])->save();
        $department = Department::factory()->create();

        $this->withHeaders($this->authHeaders($manager))
            ->postJson('/api/categories', [
                'name' => 'Inactive attempt',
                'priority' => 1,
                'department_ids' => [$department->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, Category::query()->count());
    }
}
