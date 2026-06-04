<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// NOTE: `created_by_manager_id` is set only by the controller via forceFill or
// direct property assignment after create — never from client request data and
// not mass-assignable. `is_main_manager` is intentionally omitted from
// fillable: the initial main manager is provisioned only through trusted
// operational seeding or direct administration, never via the public API.
#[Fillable(['username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Manager extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_main_manager' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_main_manager' => 'boolean',
        ];
    }

    /**
     * The departments this manager belongs to (one or more).
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_manager');
    }

    /**
     * The districts this manager is assigned to (many-to-many).
     *
     * @return BelongsToMany<District, $this>
     */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'district_manager');
    }

    /**
     * The manager who created this manager, if any.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'created_by_manager_id');
    }

    /**
     * The managers that this manager has created.
     */
    public function createdManagers(): HasMany
    {
        return $this->hasMany(Manager::class, 'created_by_manager_id');
    }

    public function blockedKeywords(): HasMany
    {
        return $this->hasMany(BlockedKeyword::class, 'added_by_manager_id');
    }

    public function flagReviews(): HasMany
    {
        return $this->hasMany(ContentFlag::class, 'reviewed_by_manager_id');
    }

    public function reportSnapshots(): HasMany
    {
        return $this->hasMany(ReportSnapshot::class, 'generated_by_manager_id');
    }

    public function userReviews(): HasMany
    {
        return $this->hasMany(UserReview::class, 'reviewed_by_manager_id');
    }

    /**
     * Resolve the manager for implicit route model binding.
     *
     * Main-manager routes require `is_main_manager = true` on the target row.
     * Ordinary manager update/disable routes require `is_main_manager = false`.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $field = $field ?? $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        if (request()->routeIs('main-managers.*')) {
            $query->where('is_main_manager', true);
        } elseif (request()->routeIs('managers.update', 'managers.disable')) {
            $query->where('is_main_manager', false);
        }

        return $query->first();
    }
}
