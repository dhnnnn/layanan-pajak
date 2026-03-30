<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimpaduTaxPayerRealization extends Model
{
    use HasUuids;

    protected $fillable = [
        'tax_type_id',
        'year',
        'npwpd',
        'nm_wp',
        'kd_kecamatan',
        'total_realization',
        'last_sync_at',
    ];

    protected $casts = [
        'total_realization' => 'float',
        'last_sync_at' => 'datetime',
    ];

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }
}
