<?php

namespace App\Models;

use Database\Factories\TaxRealizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRealization extends Model
{
    /** @use HasFactory<TaxRealizationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'tax_type_id',
        'district_id',
        'user_id',
        'year',
        'target',
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'target' => 'decimal:2',
            'january' => 'decimal:2',
            'february' => 'decimal:2',
            'march' => 'decimal:2',
            'april' => 'decimal:2',
            'may' => 'decimal:2',
            'june' => 'decimal:2',
            'july' => 'decimal:2',
            'august' => 'decimal:2',
            'september' => 'decimal:2',
            'october' => 'decimal:2',
            'november' => 'decimal:2',
            'december' => 'decimal:2',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
