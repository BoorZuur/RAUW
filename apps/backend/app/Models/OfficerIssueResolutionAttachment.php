<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'officer_issue_resolution_id',
    'file_path',
    'file_url',
    'original_name',
    'file_type',
    'file_size',
    'uploaded_at',
])]
class OfficerIssueResolutionAttachment extends Model
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
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(OfficerIssueResolution::class, 'officer_issue_resolution_id');
    }
}
