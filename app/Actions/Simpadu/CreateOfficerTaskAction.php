<?php

namespace App\Actions\Simpadu;

use App\Models\OfficerTask;

class CreateOfficerTaskAction
{
    /**
     * @param  array{tax_payer_id: string, tax_payer_name: string, tax_payer_address?: string, officer_id: int, district_id: int, amount_sptpd: numeric-string, amount_paid: numeric-string, notes?: string}  $validated
     */
    public function __invoke(array $validated): OfficerTask
    {
        return OfficerTask::query()->create([
            ...$validated,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }
}
