<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'postal_prefix', 'center_lat', 'center_lng', 'radius_meters', 'is_active'])]
class District extends Model
{
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

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }
}
