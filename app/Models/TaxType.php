<?php

namespace App\Models;

use Database\Factories\TaxTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxType extends Model
{
    /** @use HasFactory<TaxTypeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'code'];

    public function taxTargets(): HasMany
    {
        return $this->hasMany(TaxTarget::class);
    }

    public function taxRealizations(): HasMany
    {
        return $this->hasMany(TaxRealization::class);
    }
}
