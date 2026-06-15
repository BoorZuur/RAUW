<?php

namespace App\Models;

use App\Enums\IssueStatus;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'user_id',
    'category_id',
    'assigned_officer_id',
    'district_id',
    'duplicate_of_id',
    'title',
    'content',
    'postal_code',
    'address',
    'latitude',
    'longitude',
    'status',
    'priority',
    'visibility',
    'is_anonymous',
    'anonymous_alias',
])]
class Issue extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => IssueStatus::Open->value,
        'duplicate_count' => 0,
        'participant_count' => 0,
        'is_flagged' => false,
        'visibility' => Visibility::Visible->value,
        'is_anonymous' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'priority' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'duplicate_count' => 'integer',
            'participant_count' => 'integer',
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

    /**
     * Departments this issue is assigned to via the pivot table.
     *
     * This pivot is the source of truth for an issue's department assignments
     * and supports multiple departments per issue.
     *
     * @return BelongsToMany<\App\Models\Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Department::class, 'department_issue');
    }

    /**
     * The department codes currently assigned to this issue.
     *
     * @return Collection<int, string>
     */
    public function departmentCodes(): Collection
    {
        return $this->departments->pluck('code')->values();
    }

    /**
     * Sync the issue's assigned departments by their model identifiers.
     *
     * @param  iterable<int>  $departmentIds
     */
    public function syncDepartments(iterable $departmentIds): void
    {
        $this->departments()->sync($departmentIds);
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

    /**
     * The actor's participation row when this issue is the canonical target.
     *
     * Controllers may also set this relation on duplicate children after resolving
     * participation against the parent canonical id.
     */
    public function actorParticipant(): HasOne
    {
        return $this->hasOne(IssueParticipant::class, 'issue_id');
    }

    /**
     * Participation row for a specific user on this canonical issue.
     */
    public function participationFor(User $user): HasOne
    {
        return $this->hasOne(IssueParticipant::class, 'issue_id')
            ->where('user_id', $user->getKey());
    }

    /**
     * Eager-set the actor's participation row against the canonical issue id.
     *
     * For duplicate children, participation lives on the parent canonical row,
     * not the child issue id. Controllers call this before serializing so
     * {@see IssueParticipantVisibility} avoids per-request queries on show.
     */
    public function loadActorParticipant(User $user): self
    {
        $canonicalId = $this->duplicate_of_id ?? $this->getKey();

        $participant = IssueParticipant::query()
            ->where('issue_id', $canonicalId)
            ->where('user_id', $user->getKey())
            ->first();

        $this->setRelation('actorParticipant', $participant);

        return $this;
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(IssueChat::class);
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

    public function officerResolution(): HasOne
    {
        return $this->hasOne(OfficerIssueResolution::class);
    }

    public function officerUpdates(): HasMany
    {
        return $this->hasMany(OfficerIssueUpdate::class);
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
