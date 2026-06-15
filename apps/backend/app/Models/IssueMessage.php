<?php

namespace App\Models;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'issue_chat_id',
    'issue_id',
    'message_type',
    'sender_type',
    'user_id',
    'officer_id',
    'content',
    'meta',
    'is_read',
])]
class IssueMessage extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'message_type' => IssueMessageType::Message->value,
        'is_flagged' => false,
        'is_read' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_type' => IssueMessageType::class,
            'sender_type' => IssueMessageSenderType::class,
            'meta' => 'array',
            'is_flagged' => 'boolean',
            'is_read' => 'boolean',
        ];
    }

    public function issueChat(): BelongsTo
    {
        return $this->belongsTo(IssueChat::class);
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

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueMessageAttachment::class);
    }

    public function contentFlags(): HasMany
    {
        return $this->hasMany(ContentFlag::class, 'message_id');
    }
}
