<?php

namespace App\Models;

use Database\Factories\TaxTargetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTarget extends Model
{
    /** @use HasFactory<TaxTargetFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['tax_type_id', 'year', 'target_amount'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'target_amount' => 'decimal:2',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }
}
