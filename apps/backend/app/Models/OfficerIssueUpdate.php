<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'issue_id',
    'officer_id',
    'title',
    'content',
])]
class OfficerIssueUpdate extends Model
{
    use HasFactory;

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OfficerIssueUpdateAttachment::class);
    }
}
