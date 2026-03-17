<?php

namespace App\Actions\Upt;

use App\Models\UptComparison;

class StoreUptComparisonAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): UptComparison
    {
        return UptComparison::query()->updateOrCreate(
            [
                'tax_type_id' => $data['tax_type_id'],
                'upt_id' => $data['upt_id'],
                'year' => $data['year'],
            ],
            [
                'target_amount' => $data['target_amount'],
            ],
        );
    }
}
