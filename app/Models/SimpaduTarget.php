<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpaduTarget extends Model
{
    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'no_ayat',
        'year',
        'keterangan',
        'total_target',
        'q1_pct',
        'q2_pct',
        'q3_pct',
        'q4_pct',
    ];

    protected $casts = [
        'total_target' => 'decimal:2',
        'q1_pct' => 'decimal:2',
        'q2_pct' => 'decimal:2',
        'q3_pct' => 'decimal:2',
        'q4_pct' => 'decimal:2',
    ];
}
