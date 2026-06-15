<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryDepartmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------
    // Tables and many-to-many relation
    // ---------------------------------------------------------------------

    public function test_departments_and_pivot_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('departments'));
        $this->assertTrue(Schema::hasTable('category_department'));
        $this->assertTrue(Schema::hasColumns('departments', ['code', 'name', 'is_active']));
        $this->assertTrue(Schema::hasColumns('category_department', ['category_id', 'department_id']));
    }

    public function test_category_can_be_assigned_to_multiple_departments(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        $category = Category::factory()->create();

        $category->departments()->sync([$departmentA->id, $departmentB->id]);

        $this->assertEqualsCanonicalizing(
            [$departmentA->id, $departmentB->id],
            $category->departments()->pluck('departments.id')->all()
        );
        // The relation works in both directions.
        $this->assertTrue($departmentA->categories()->where('categories.id', $category->id)->exists());
    }

    public function test_pivot_enforces_unique_category_department_pairs(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create();

        $category->departments()->attach($department->id);
        // Re-syncing the same pair must not create duplicate pivot rows.
        $category->departments()->sync([$department->id]);

        $this->assertSame(1, DB::table('category_department')
            ->where('category_id', $category->id)
            ->where('department_id', $department->id)
            ->count());
    }

    // ---------------------------------------------------------------------
    // Pivot cascade on department deletion
    // ---------------------------------------------------------------------

    public function test_deleting_a_department_cascades_pivot_rows_only(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();

        $department->delete();

        $this->assertDatabaseMissing('category_department', [
            'department_id' => $department->id,
        ]);
        // The category record itself must remain intact.
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_cascades_pivot_rows_only(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->withDepartments($department)->create();

        $category->delete();

        $this->assertDatabaseMissing('category_department', [
            'category_id' => $category->id,
        ]);
        // The department record itself must remain intact.
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    // ---------------------------------------------------------------------
    // Category hierarchy and ordering
    // ---------------------------------------------------------------------

    public function test_category_hierarchy_uses_parent_id(): void
    {
        $main = Category::factory()->create();
        $sub = Category::factory()->subcategoryOf($main)->create();

        $this->assertNull($main->parent_id);
        $this->assertSame($main->id, $sub->parent_id);
        $this->assertTrue($main->children()->where('categories.id', $sub->id)->exists());
        $this->assertSame($main->id, $sub->parent->id);
    }

    public function test_lower_priority_values_sort_ahead_of_higher_values(): void
    {
        $low = Category::factory()->create(['priority' => 9]);
        $high = Category::factory()->create(['priority' => 1]);
        $mid = Category::factory()->create(['priority' => 5]);

        $ordered = Category::query()
            ->whereNull('parent_id')
            ->orderBy('priority')
            ->pluck('id')
            ->all();

        $this->assertSame([$high->id, $mid->id, $low->id], $ordered);
    }

    public function test_subcategories_sort_by_name(): void
    {
        $main = Category::factory()->create();
        $zebra = Category::factory()->subcategoryOf($main)->create(['name' => 'Zebra']);
        $alpha = Category::factory()->subcategoryOf($main)->create(['name' => 'Alpha']);

        $ordered = $main->children()->orderBy('name')->pluck('id')->all();

        $this->assertSame([$alpha->id, $zebra->id], $ordered);
    }

    public function test_priority_may_be_null(): void
    {
        $category = Category::factory()->create(['priority' => null]);

        $category->refresh();

        $this->assertNull($category->priority);
    }
}
