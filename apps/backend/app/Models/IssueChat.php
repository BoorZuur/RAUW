<?php

namespace App\Models;

use App\Enums\ChatStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'issue_id',
    'user_id',
    'status',
    'opened_by_officer_id',
    'closed_by_officer_id',
])]
class IssueChat extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ChatStatus::Closed->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChatStatus::class,
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openedByOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'opened_by_officer_id');
    }

    public function closedByOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'closed_by_officer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IssueMessage::class);
    }
}
