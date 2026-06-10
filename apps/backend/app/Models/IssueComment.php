<?php

namespace App\Models;

use App\Enums\ActorType;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'issue_id',
    'author_type',
    'user_id',
    'officer_id',
    'manager_id',
    'content',
    'visibility',
])]
class IssueComment extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_flagged' => false,
        'visibility' => Visibility::Visible->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'author_type' => ActorType::class,
            'is_flagged' => 'boolean',
            'visibility' => Visibility::class,
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

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function contentFlags(): HasMany
    {
        return $this->hasMany(ContentFlag::class, 'comment_id');
    }
}
