<?php

namespace App\Actions\Upt;

use App\Models\SimpaduTarget;

class PreviewUptAdditionalTargetAction
{
    public function __construct(
        private readonly DistributeAdditionalTargetByPctAction $distribute,
    ) {}

    /**
     * Hitung preview distribusi target tambahan per tribulan.
     *
     * @return array{no_ayat: string, keterangan: string, year: int, start_quarter: int, total_target_awal: float, total_tambahan: float, total_target_baru: float, quarters: array}|array{error: string}
     */
    public function __invoke(string $noAyat, float $additionalTarget, int $year, int $currentQuarter): array
    {
        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $year)
            ->first();

        if (! $target) {
            return ['error' => 'Data target tidak ditemukan.'];
        }

        $totalTarget = (float) $target->total_target;

        $pcts = [
            1 => (float) $target->q1_pct,
            2 => (float) $target->q2_pct,
            3 => (float) $target->q3_pct,
            4 => (float) $target->q4_pct,
        ];

        $originalTargets = [
            1 => $totalTarget * ($pcts[1] / 100),
            2 => $totalTarget * (($pcts[2] - $pcts[1]) / 100),
            3 => $totalTarget * (($pcts[3] - $pcts[2]) / 100),
            4 => $totalTarget * (($pcts[4] - $pcts[3]) / 100),
        ];

        $additionalPerQ = ($this->distribute)($additionalTarget, $currentQuarter, $target);

        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = [
                'target_awal' => $originalTargets[$q],
                'tambahan' => $additionalPerQ[$q],
                'target_baru' => $originalTargets[$q] + $additionalPerQ[$q],
            ];
        }

        return [
            'no_ayat' => $noAyat,
            'keterangan' => $target->keterangan,
            'year' => $year,
            'start_quarter' => $currentQuarter,
            'total_target_awal' => $totalTarget,
            'total_tambahan' => $additionalTarget,
            'total_target_baru' => $totalTarget + $additionalTarget,
            'quarters' => $quarters,
        ];
    }
}
