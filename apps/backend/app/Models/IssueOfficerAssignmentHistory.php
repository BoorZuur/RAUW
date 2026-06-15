<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueOfficerAssignmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'officer_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
