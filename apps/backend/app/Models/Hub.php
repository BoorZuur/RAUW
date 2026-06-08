<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'postal_code', 'latitude', 'longitude', 'is_active', 'radius_meters'])]
class Hub extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => false,
        'radius_meters' => 100,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'radius_meters' => 'integer',
        ];
    }

    /**
     * The districts belonging to this hub.
     *
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * The officers assigned to this hub.
     *
     * @return HasMany<Officer, $this>
     */
    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }

    /**
     * The managers assigned to this hub.
     *
     * @return HasMany<Manager, $this>
     */
    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }
}
