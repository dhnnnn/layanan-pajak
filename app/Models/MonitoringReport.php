<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringReport extends Model
{
    protected $fillable = [
        'maps_discovery_result_id',
        'officer_id',
        'monitoring_date',
        'monitoring_hour',
        'day_of_week',
        'visitor_count',
        'parking_bus',
        'parking_elf',
        'parking_mobil',
        'parking_motor',
        'photos',
        'latitude',
        'longitude',
        'notes',
        'validation_status',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_date' => 'date',
            'visitor_count' => 'integer',
            'parking_bus' => 'integer',
            'parking_elf' => 'integer',
            'parking_mobil' => 'integer',
            'parking_motor' => 'integer',
            'photos' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function mapsDiscoveryResult(): BelongsTo
    {
        return $this->belongsTo(MapsDiscoveryResult::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function potentialCalculations(): HasMany
    {
        return $this->hasMany(PotentialCalculation::class);
    }
}
