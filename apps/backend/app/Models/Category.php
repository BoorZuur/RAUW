<?php

namespace App\Models;

use App\Enums\Department as DepartmentEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'weight', 'priority', 'parent_id', 'is_active'])]
class Category extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Both `priority` (used by main categories, where `parent_id` is null) and
     * `weight` (used by subcategories) follow a lower-number-is-higher-priority
     * ordering and may be null when ordering is unspecified.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Departments this category is assigned to via the pivot table.
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'category_department');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * The department model identifiers assigned to this category.
     *
     * This drives issue department auto-assignment: an issue inherits its
     * departments from its selected category through the shared pivot source
     * of truth, which supports multiple departments per issue. The loaded
     * `departments` relation is reused when available to avoid extra queries.
     *
     * @return list<int>
     */
    public function departmentIds(): array
    {
        return $this->departments->pluck('id')->all();
    }

    /**
     * Derive the legacy single-department enum value from the category's
     * department assignments.
     *
     * The many-to-many pivot is the source of truth for an issue's
     * departments, but the issues table retains a non-nullable `department`
     * enum column for backwards compatibility. A category assigned to both
     * real departments collapses to the legacy `Both` value, while a single
     * assignment maps to its own code. Null is only possible for a category
     * with no department assignments, which category validation forbids.
     */
    public function legacyDepartment(): ?DepartmentEnum
    {
        $codes = $this->departments->pluck('code')->all();

        $hasDistrictManagement = in_array(DepartmentEnum::DistrictManagement->value, $codes, true);
        $hasBoaYouth = in_array(DepartmentEnum::BoaYouth->value, $codes, true);

        return match (true) {
            $hasDistrictManagement && $hasBoaYouth => DepartmentEnum::Both,
            $hasDistrictManagement => DepartmentEnum::DistrictManagement,
            $hasBoaYouth => DepartmentEnum::BoaYouth,
            default => null,
        };
    }
}
