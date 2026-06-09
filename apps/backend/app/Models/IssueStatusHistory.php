<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'issue_id',
    'changed_by_officer_id',
    'old_status',
    'new_status',
    'note',
    'changed_at',
])]
class IssueStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_status' => IssueStatus::class,
            'new_status' => IssueStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function changedByOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'changed_by_officer_id');
    }
}
