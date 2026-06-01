<?php

namespace App\Models;

use App\Enums\FlagAction;
use App\Enums\FlagReason;
use App\Enums\FlagSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'issue_id',
    'comment_id',
    'message_id',
    'flagged_by_officer_id',
    'reviewed_by_manager_id',
    'flag_source',
    'matched_keyword',
    'flag_reason',
    'counts_toward_review',
    'action_taken',
])]
class ContentFlag extends Model
{
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag_source' => FlagSource::class,
            'flag_reason' => FlagReason::class,
            'counts_toward_review' => 'boolean',
            'action_taken' => FlagAction::class,
            'flagged_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(IssueComment::class, 'comment_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(IssueMessage::class, 'message_id');
    }

    public function flaggedByOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'flagged_by_officer_id');
    }

    public function reviewedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'reviewed_by_manager_id');
    }
}
