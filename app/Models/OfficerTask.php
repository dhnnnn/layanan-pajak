<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'tax_payer_id',
        'tax_payer_name',
        'tax_payer_address',
        'tax_type_code',
        'officer_id',
        'district_id',
        'status',
        'amount_sptpd',
        'amount_paid',
        'notes',
        'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'amount_sptpd' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
