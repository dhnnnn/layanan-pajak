<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapsDiscoveryResult extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'user_id',
        'title',
        'subtitle',
        'category',
        'place_id',
        'url',
        'latitude',
        'longitude',
        'rating',
        'reviews',
        'price_range',
        'status',
        'matched_npwpd',
        'matched_name',
        'similarity_score',
        'tax_type_code',
        'district_name',
        'keyword',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'float',
            'reviews' => 'integer',
            'similarity_score' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
