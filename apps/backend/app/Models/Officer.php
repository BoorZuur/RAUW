<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['username', 'email', 'password', 'badge_number'])]
#[Hidden(['password', 'remember_token'])]
class Officer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The districts this officer is assigned to (many-to-many).
     *
     * @return BelongsToMany<District, $this>
     */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'district_officer');
    }

    /**
     * The departments this officer belongs to (one or more).
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_officer');
    }

    public function assignedIssues(): HasMany
    {
        return $this->hasMany(Issue::class, 'assigned_officer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IssueMessage::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(IssueStatusHistory::class, 'changed_by_officer_id');
    }

    public function chatClosedIssues(): HasMany
    {
        return $this->hasMany(Issue::class, 'chat_closed_by_officer_id');
    }

    public function flaggedContent(): HasMany
    {
        return $this->hasMany(ContentFlag::class, 'flagged_by_officer_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(OfficerSession::class);
    }
}
