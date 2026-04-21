<?php

namespace App\Actions\Upt;

use App\Models\SimpaduTarget;

class GetAyatPctAction
{
    /**
     * Ambil proporsi pct per tribulan untuk satu ayat.
     * q_pct bersifat kumulatif, kembalikan selisih per tribulan.
     *
     * @return array{pcts: array<int, float>, base_target: float}
     */
    public function __invoke(string $noAyat, int $year): array
    {
        $default = ['pcts' => [1 => 25.0, 2 => 25.0, 3 => 25.0, 4 => 25.0], 'base_target' => 0.0];

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $year)
            ->first();

        if (! $target) {
            return $default;
        }

        $q1 = (float) $target->q1_pct;
        $q2 = (float) $target->q2_pct;
        $q3 = (float) $target->q3_pct;
        $q4 = (float) $target->q4_pct;

        return [
            'pcts' => [
                1 => $q1,
                2 => $q2 - $q1,
                3 => $q3 - $q2,
                4 => $q4 - $q3,
            ],
            'base_target' => (float) $target->total_target,
        ];
    }
}
