<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpaduTaxPayer extends Model
{
    protected $fillable = [
        'npwpd',
        'nop',
        'year',
        'nm_wp',
        'nm_op',
        'almt_op',
        'kd_kecamatan',
        'total_ketetapan',
        'total_bayar',
        'total_tunggakan',
        'ayat',
    ];

    protected $casts = [
        'total_ketetapan' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'total_tunggakan' => 'decimal:2',
    ];
}
