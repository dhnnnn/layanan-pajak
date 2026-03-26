<?php

namespace App\Models;

use Database\Factories\TaxTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxType extends Model
{
    /** @use HasFactory<TaxTypeFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['name', 'code', 'parent_id'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TaxType $taxType): void {
            if (empty($taxType->code)) {
                $taxType->code = self::generateCode($taxType->name, $taxType->parent_id);
            }
        });
    }

    private static function generateCode(string $name, ?string $parentId = null): string
    {
        if ($parentId !== null) {
            $parent = self::query()->find($parentId);

            if ($parent !== null) {
                $baseCode = $parent->code.'-'.strtoupper(implode('', array_map(
                    fn (string $word) => strtoupper(substr($word, 0, 1)),
                    array_filter(
                        preg_split('/\s+/', trim($name)) ?: [],
                        fn (string $word) => ! in_array(strtolower($word), ['dan', 'atau', 'di', 'ke', 'dari', 'untuk'])
                    )
                )));

                $code = $baseCode;
                $counter = 1;

                while (self::query()->where('code', $code)->exists()) {
                    $code = $baseCode.'-'.$counter;
                    $counter++;
                }

                return $code;
            }
        }

        // Generate code from name using acronym
        // "Pajak Bumi dan Bangunan" -> "PBB"
        $words = preg_split('/\s+/', trim($name));
        $acronym = '';

        foreach ($words as $word) {
            // Skip common words
            if (in_array(strtolower($word), ['dan', 'atau', 'di', 'ke', 'dari', 'untuk'])) {
                continue;
            }
            $acronym .= strtoupper(substr($word, 0, 1));
        }

        // If acronym is too short, use first 3-4 chars of first word
        if (strlen($acronym) < 2) {
            $acronym = strtoupper(substr($words[0], 0, 4));
        }

        $code = 'TAX-'.$acronym;

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (self::query()->where('code', $code)->exists()) {
            $code = $originalCode.'-'.$counter;
            $counter++;
        }

        return $code;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TaxType::class, 'parent_id');
    }

    public function taxTargets(): HasMany
    {
        return $this->hasMany(TaxTarget::class);
    }

    public function taxRealizations(): HasMany
    {
        return $this->hasMany(TaxRealization::class);
    }

    public function uptComparisons(): HasMany
    {
        return $this->hasMany(UptComparison::class);
    }
}
