<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotentialCalculation extends Model
{
    protected $fillable = [
        'maps_discovery_result_id',
        'monitoring_report_id',
        'checker_result',
        'maps_hour_count',
        'maps_weekly_total',
        'avg_duration_hours',
        'avg_menu_price',
        'weekly_visitors',
        'weekly_potential_tax',
        'monthly_potential_tax',
        'min_potential_tax',
        'max_potential_tax',
        'calculation_date',
    ];

    protected function casts(): array
    {
        return [
            'checker_result' => 'integer',
            'maps_hour_count' => 'integer',
            'maps_weekly_total' => 'integer',
            'avg_duration_hours' => 'decimal:2',
            'avg_menu_price' => 'decimal:2',
            'weekly_visitors' => 'integer',
            'weekly_potential_tax' => 'decimal:2',
            'monthly_potential_tax' => 'decimal:2',
            'min_potential_tax' => 'decimal:2',
            'max_potential_tax' => 'decimal:2',
            'calculation_date' => 'date',
        ];
    }

    public function mapsDiscoveryResult(): BelongsTo
    {
        return $this->belongsTo(MapsDiscoveryResult::class);
    }

    public function monitoringReport(): BelongsTo
    {
        return $this->belongsTo(MonitoringReport::class);
    }
}
