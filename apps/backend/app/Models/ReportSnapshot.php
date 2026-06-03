<?php

namespace App\Models;

use App\Enums\ReportPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'generated_by_manager_id',
    'period',
    'period_start',
    'period_end',
    'avg_resolution_days',
    'metrics',
    'satisfaction_rate',
])]
class ReportSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'period' => ReportPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'total_issues' => 'integer',
            'open_issues' => 'integer',
            'resolved_issues' => 'integer',
            'generated_at' => 'datetime',
            'avg_resolution_days' => 'decimal:2',
            'metrics' => 'array',
            'satisfaction_rate' => 'decimal:2',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_issues' => 0,
        'open_issues' => 0,
        'resolved_issues' => 0,
    ];

    public function generatedByManager(): BelongsTo
    {
        return $this->belongsTo(Manager::class, 'generated_by_manager_id');
    }
}
