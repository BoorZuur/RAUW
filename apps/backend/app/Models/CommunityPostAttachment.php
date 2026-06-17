<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['community_post_id', 'file_path', 'file_url', 'original_name', 'file_type', 'file_size', 'uploaded_at'])]
class CommunityPostAttachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function communityPost(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class);
    }
}

