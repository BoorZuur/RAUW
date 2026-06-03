<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'officer_id',
    'shift_start',
    'shift_end',
    'start_lat',
    'start_lng',
    'last_lat',
    'last_lng',
    'last_seen_at',
])]
class OfficerSession extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shift_start' => 'datetime',
            'shift_end' => 'datetime',
            'start_lat' => 'decimal:8',
            'start_lng' => 'decimal:8',
            'last_lat' => 'decimal:8',
            'last_lng' => 'decimal:8',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}
