<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['keyword', 'added_by_manager_id'])]
class BlockedKeyword extends Model
{
    public $timestamps = false;

    public function addedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'added_by_manager_id');
    }
}
