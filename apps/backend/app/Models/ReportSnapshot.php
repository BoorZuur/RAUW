<?php

namespace App\Models;

use App\Enums\ReportPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'generated_by_manager_id',
    'period',
    'period_start',
    'period_end',
    'total_issues',
    'open_issues',
    'resolved_issues',
    'avg_resolution_days',
    'count_laag',
    'count_midden',
    'count_zwaar',
    'count_wijkbeheer',
    'count_boa_jeugd',
    'satisfaction_rate',
])]
class ReportSnapshot extends Model
{
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => ReportPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
            'avg_resolution_days' => 'decimal:2',
            'satisfaction_rate' => 'decimal:2',
        ];
    }

    public function generatedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'generated_by_manager_id');
    }
}
