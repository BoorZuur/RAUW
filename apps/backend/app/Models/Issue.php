<?php

namespace App\Models;

use App\Enums\ChatStatus;
use App\Enums\Department;
use App\Enums\IssueStatus;
use App\Enums\Priority;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'category_id',
    'assigned_officer_id',
    'district_id',
    'duplicate_of_id',
    'chat_closed_by_officer_id',
    'title',
    'content',
    'neighborhood',
    'postal_code',
    'address',
    'latitude',
    'longitude',
    'status',
    'chat_status',
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
            'status' => IssueStatus::class,
            'chat_status' => ChatStatus::class,
            'priority' => Priority::class,
            'department' => Department::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'duplicate_count' => 'integer',
            'participant_count' => 'integer',
            'vote_count' => 'integer',
            'is_flagged' => 'boolean',
            'visibility' => Visibility::class,
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

    public function chatClosedByOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'chat_closed_by_officer_id');
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

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IssueMessage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueAttachment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(IssueStatusHistory::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(IssueResolution::class);
    }

    public function domainNotifications(): HasMany
    {
        return $this->hasMany(DomainNotification::class);
    }

    public function contentFlags(): HasMany
    {
        return $this->hasMany(ContentFlag::class);
    }
}
