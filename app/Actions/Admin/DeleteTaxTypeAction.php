<?php

namespace App\Actions\Admin;

use App\Models\TaxType;
use Illuminate\Validation\ValidationException;

class DeleteTaxTypeAction
{
    public function __invoke(TaxType $taxType): void
    {
        if ($taxType->children()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Tidak dapat menghapus jenis pajak yang masih memiliki subbab. Hapus semua subbabnya terlebih dahulu.',
            ]);
        }

        $taxType->delete();
    }
}
