<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $table = 'villages';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'district_code',
        'name',
        'type',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'district_code');
    }
}
