<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable([
    'officer_id',
    'personal_access_token_id',
    'hub_id',
    'shift_start',
    'shift_end',
    'start_lat',
    'start_lng',
    'distance_meters_at_login',
    'is_hub_active',
    'hub_active_until',
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
        'is_hub_active' => false,
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
            'distance_meters_at_login' => 'integer',
            'is_hub_active' => 'boolean',
            'hub_active_until' => 'datetime',
            'last_lat' => 'decimal:8',
            'last_lng' => 'decimal:8',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The officer this session belongs to.
     *
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * The hub assigned at login time.
     *
     * @return BelongsTo<Hub, $this>
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * The Sanctum token issued for this login.
     *
     * @return BelongsTo<PersonalAccessToken, $this>
     */
    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }
}
