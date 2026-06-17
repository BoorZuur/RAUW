<?php

namespace App\Models;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'officer_id',
    'district_id',
    'title',
    'content',
    'visibility',
])]
class CommunityPost extends Model
{
    use HasFactory;

    protected $attributes = [
        'visibility' => Visibility::Visible->value,
    ];

    protected function casts(): array
    {
        return [
            'visibility' => Visibility::class,
        ];
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CommunityPostAttachment::class);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_post_user');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DomainNotification::class);
    }
}

