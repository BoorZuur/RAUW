<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'is_active'])]
class Department extends Model
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Categories assigned to this department via the pivot table.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_department');
    }

    /**
     * Managers assigned to this department (each manager has exactly one).
     *
     * @return HasMany<Manager, $this>
     */
    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }

    /**
     * Officers assigned to this department via the pivot table.
     *
     * @return BelongsToMany<Officer, $this>
     */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(Officer::class, 'department_officer');
    }
}
