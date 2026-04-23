<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictAdditionalTarget extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'district_id',
        'no_ayat',
        'year',
        'additional_target',
        'start_quarter',
        'q1_additional',
        'q2_additional',
        'q3_additional',
        'q4_additional',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_quarter' => 'integer',
            'additional_target' => 'decimal:2',
            'q1_additional' => 'decimal:2',
            'q2_additional' => 'decimal:2',
            'q3_additional' => 'decimal:2',
            'q4_additional' => 'decimal:2',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
