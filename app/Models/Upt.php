<?php

namespace App\Models;

use Database\Factories\UptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upt extends Model
{
    /** @use HasFactory<UptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'code', 'description'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Upt $upt): void {
            if (empty($upt->code)) {
                $upt->code = self::generateCode($upt->name);
            }
        });
    }

    private static function generateCode(string $name): string
    {
        // Generate code from name: "UPT I" -> "UPT-I", "UPT Utara" -> "UPT-UTARA"
        $name = trim($name);

        // Remove "UPT" prefix if exists
        $name = preg_replace('/^UPT\s+/i', '', $name);

        $code = 'UPT-'.strtoupper(str_replace(' ', '-', $name));

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (self::query()->where('code', $code)->exists()) {
            $code = $originalCode.'-'.$counter;
            $counter++;
        }

        return $code;
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'upt_districts');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(UptComparison::class);
    }
}
