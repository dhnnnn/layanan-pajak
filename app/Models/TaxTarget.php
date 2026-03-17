<?php

namespace App\Models;

use Database\Factories\TaxTargetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTarget extends Model
{
    /** @use HasFactory<TaxTargetFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['tax_type_id', 'year', 'target_amount', 'q1_target', 'q2_target', 'q3_target', 'q4_target', 'q1_percentage', 'q2_percentage', 'q3_percentage', 'q4_percentage'];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'target_amount' => 'decimal:2',
            'q1_target' => 'decimal:2',
            'q2_target' => 'decimal:2',
            'q3_target' => 'decimal:2',
            'q4_target' => 'decimal:2',
            'q1_percentage' => 'decimal:2',
            'q2_percentage' => 'decimal:2',
            'q3_percentage' => 'decimal:2',
            'q4_percentage' => 'decimal:2',
        ];
    }

    public function getQ1Percentage(): float
    {
        return $this->q1_percentage ?? ($this->target_amount > 0 ? ($this->q1_target / $this->target_amount * 100) : 0);
    }

    public function getQ2Percentage(): float
    {
        return $this->q2_percentage ?? ($this->target_amount > 0 ? ($this->q2_target / $this->target_amount * 100) : 0);
    }

    public function getQ3Percentage(): float
    {
        return $this->q3_percentage ?? ($this->target_amount > 0 ? ($this->q3_target / $this->target_amount * 100) : 0);
    }

    public function getQ4Percentage(): float
    {
        return $this->q4_percentage ?? 100;
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }
}
