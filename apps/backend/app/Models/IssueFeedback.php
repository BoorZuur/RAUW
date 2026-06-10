<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueFeedback extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'reviewer_user_id',
        'is_satisfied',
        'comment',
        'submitted_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_satisfied' => 'boolean',
            'submitted_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
