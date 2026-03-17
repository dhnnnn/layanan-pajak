<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    use HasUuids;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['number', 'name', 'abbreviation', 'column_name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    /**
     * Returns a map of Indonesian heading name (used in Excel) to DB column name.
     *
     * @return array<string, string>
     */
    public static function headingToColumnMap(): array
    {
        return static::query()
            ->orderBy('number')
            ->get()
            ->mapWithKeys(
                fn (self $month): array => [
                    mb_strtolower($month->name) => $month->column_name,
                ],
            )
            ->all();
    }

    /**
     * Returns an ordered list of column names for tax_realizations.
     *
     * @return list<string>
     */
    public static function columnNames(): array
    {
        return static::query()->orderBy('number')->pluck('column_name')->all();
    }

    /**
     * Returns an ordered list of Indonesian month names.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return static::query()->orderBy('number')->pluck('name')->all();
    }
}
