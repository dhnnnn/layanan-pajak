<?php

namespace App\Actions\Upt;

use App\Models\SimpaduTarget;
use App\Models\UptAdditionalTarget;
use Illuminate\Support\Facades\Cache;

class StoreUptAdditionalTargetAction
{
    public function __construct(
        private readonly DistributeAdditionalTargetByPctAction $distribute,
    ) {}

    public function __invoke(
        string $noAyat,
        float $total,
        int $startQ,
        int $year,
        ?string $notes,
        string $createdBy,
    ): UptAdditionalTarget {
        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $year)
            ->first();

        $quarters = ($this->distribute)($total, $startQ, $target);

        $record = UptAdditionalTarget::query()->updateOrCreate(
            ['no_ayat' => $noAyat, 'year' => $year],
            [
                'additional_target' => $total,
                'start_quarter' => $startQ,
                'q1_additional' => $quarters[1],
                'q2_additional' => $quarters[2],
                'q3_additional' => $quarters[3],
                'q4_additional' => $quarters[4],
                'notes' => $notes,
                'created_by' => $createdBy,
            ]
        );

        Cache::forget("dashboard:tax:{$year}");

        return $record;
    }
}
