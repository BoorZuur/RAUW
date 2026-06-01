<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'issue_id',
    'comment_id',
    'message_id',
    'reviewed_by_manager_id',
    'matched_keyword',
    'action_taken',
])]
class FlaggedContentLog extends Model
{
    protected $table = 'flagged_content_log';

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function reviewedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'reviewed_by_manager_id');
    }
}
