<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['name', 'code'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (District $district): void {
            if (empty($district->code)) {
                $district->code = self::generateCode($district->name);
            }
        });
    }

    private static function generateCode(string $name): string
    {
        // Generate code from name: "Kecamatan Satu" -> "KEC-SATU"
        $words = preg_split('/\s+/', trim($name));
        $code = 'KEC-'.strtoupper(implode('-', array_slice($words, -2)));

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (self::query()->where('code', $code)->exists()) {
            $code = $originalCode.'-'.$counter;
            $counter++;
        }

        return $code;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_districts');
    }

    public function taxRealizations(): HasMany
    {
        return $this->hasMany(TaxRealization::class);
    }

    public function upts(): BelongsToMany
    {
        return $this->belongsToMany(Upt::class, 'upt_districts');
    }
}
