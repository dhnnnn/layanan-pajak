<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpaduMonthlyRealization extends Model
{
    protected $fillable = ['year', 'ayat', 'kd_kecamatan', 'month', 'total_bayar', 'synced_at'];

    protected function casts(): array
    {
        return [
            'total_bayar' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }
}
