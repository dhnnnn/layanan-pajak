<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapsStatistic extends Model
{
    protected $fillable = [
        'maps_discovery_result_id',
        'hour_range',
        'day_of_week',
        'visitor_count',
    ];

    protected function casts(): array
    {
        return [
            'visitor_count' => 'integer',
        ];
    }

    public function mapsDiscoveryResult(): BelongsTo
    {
        return $this->belongsTo(MapsDiscoveryResult::class);
    }
}
