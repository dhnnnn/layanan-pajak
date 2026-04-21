<?php

namespace App\Actions\Upt;

use App\Models\SimpaduTarget;

class DistributeAdditionalTargetByPctAction
{
    /**
     * Distribusikan total target tambahan ke per tribulan
     * mengikuti proporsi q_pct dari SimpaduTarget (kumulatif).
     *
     * @return array<int, float>
     */
    public function __invoke(float $total, int $startQ, ?SimpaduTarget $target): array
    {
        $result = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];

        if (! $target) {
            $active = 4 - $startQ + 1;
            $per = round($total / $active, 2);
            $distributed = 0.0;
            for ($q = $startQ; $q <= 4; $q++) {
                $result[$q] = $q === 4 ? round($total - $distributed, 2) : $per;
                $distributed += $result[$q];
            }

            return $result;
        }

        $pcts = [
            1 => (float) $target->q1_pct,
            2 => (float) $target->q2_pct,
            3 => (float) $target->q3_pct,
            4 => (float) $target->q4_pct,
        ];

        // Proporsi per tribulan dari selisih kumulatif
        $perQPct = [
            1 => $pcts[1],
            2 => $pcts[2] - $pcts[1],
            3 => $pcts[3] - $pcts[2],
            4 => $pcts[4] - $pcts[3],
        ];

        $activePctSum = 0.0;
        for ($q = $startQ; $q <= 4; $q++) {
            $activePctSum += $perQPct[$q];
        }

        if ($activePctSum <= 0) {
            $activePctSum = 4 - $startQ + 1;
            for ($q = $startQ; $q <= 4; $q++) {
                $perQPct[$q] = 1.0;
            }
        }

        $distributed = 0.0;
        for ($q = $startQ; $q <= 4; $q++) {
            if ($q === 4) {
                $result[$q] = round($total - $distributed, 2);
            } else {
                $result[$q] = round($total * ($perQPct[$q] / $activePctSum), 2);
                $distributed += $result[$q];
            }
        }

        return $result;
    }
}
