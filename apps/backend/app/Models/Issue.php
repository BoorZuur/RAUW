<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'category_id',
    'assigned_officer_id',
    'district_id',
    'duplicate_of_id',
    'title',
    'content',
    'neighborhood',
    'postal_code',
    'address',
    'latitude',
    'longitude',
    'status',
    'priority',
    'department',
    'duplicate_count',
    'participant_count',
    'vote_count',
    'is_flagged',
    'visibility',
    'is_anonymous',
    'anonymous_alias',
    'resolved_at',
])]
class Issue extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'duplicate_count' => 'integer',
            'participant_count' => 'integer',
            'vote_count' => 'integer',
            'is_flagged' => 'boolean',
            'is_anonymous' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'assigned_officer_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'duplicate_of_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(Issue::class, 'duplicate_of_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(IssueParticipant::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IssueVote::class);
    }
}
