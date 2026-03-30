<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSimpaduTargetsAction
{
    /**
     * Sync budget targets from Simpadu to local cache.
     */
    public function __invoke(int $year): array
    {
        Log::info("Starting Simpadu Target Sync for year: {$year}");
        
        $simpaduTargets = DB::connection('simpadunew')
            ->table('m_target_anggaran')
            ->where('thn_anggaran', $year)
            ->get();

        $count = 0;
        foreach ($simpaduTargets as $sTarget) {
            SimpaduTarget::updateOrCreate(
                ['no_ayat' => $sTarget->no_ayat, 'year' => $year],
                [
                    'keterangan' => $sTarget->keterangan,
                    'total_target' => $sTarget->target_anggaran,
                    'q1_pct' => $sTarget->tribulan_1,
                    'q2_pct' => $sTarget->tribulan_2,
                    'q3_pct' => $sTarget->tribulan_3,
                    'q4_pct' => $sTarget->tribulan_4,
                ]
            );
            $count++;
        }

        return ['count' => $count];
    }
}
