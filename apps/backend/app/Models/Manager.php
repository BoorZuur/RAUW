<?php

namespace App\Models;

use App\Enums\Department;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password', 'department', 'district_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class Manager extends Authenticatable
{
    use Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'department' => Department::class,
            'is_active' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
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
}
