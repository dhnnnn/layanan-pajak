<?php

namespace App\Actions\Admin;

use App\Models\TaxType;
use Illuminate\Validation\ValidationException;

class CreateTaxSubtypeAction
{
    /**
     * @param  array{name: string}  $data
     */
    public function __invoke(array $data, TaxType $parent): TaxType
    {
        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'Subbab tidak dapat ditambahkan ke jenis pajak yang sudah merupakan subbab.',
            ]);
        }

        return TaxType::query()->create([
            'name' => $data['name'],
            'parent_id' => $parent->id,
        ]);
    }
}
