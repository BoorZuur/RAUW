<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'hub_id', 'postal_prefix', 'center_lat', 'center_lng', 'radius_meters', 'is_active'])]
class District extends Model
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
            'center_lat' => 'decimal:8',
            'center_lng' => 'decimal:8',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The hub this district belongs to.
     *
     * @return BelongsTo<Hub, $this>
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * The officers assigned to this district (many-to-many).
     *
     * @return BelongsToMany<Officer, $this>
     */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(Officer::class, 'district_officer');
    }

    /**
     * The managers assigned to this district (many-to-many).
     *
     * @return BelongsToMany<Manager, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, 'district_manager');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function feedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'district_user');
    }
}

