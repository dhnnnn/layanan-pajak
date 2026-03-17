<?php

namespace App\Models;

use Database\Factories\UptComparisonFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptComparison extends Model
{
    /** @use HasFactory<UptComparisonFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['tax_type_id', 'upt_id', 'year', 'target_amount'];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function upt(): BelongsTo
    {
        return $this->belongsTo(Upt::class);
    }
}
