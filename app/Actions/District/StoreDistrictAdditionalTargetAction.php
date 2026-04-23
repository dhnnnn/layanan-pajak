<?php

namespace App\Actions\District;

use App\Models\District;
use App\Models\DistrictAdditionalTarget;
use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\Cache;

class StoreDistrictAdditionalTargetAction
{
    public function __construct(
        private readonly DistributeDistrictTargetByPctAction $distribute,
    ) {}

    public function __invoke(
        District $district,
        string $noAyat,
        float $total,
        int $startQ,
        ?string $notes,
        string $createdBy,
    ): DistrictAdditionalTarget {
        $currentYear = (int) now()->year;

        $target = SimpaduTarget::query()
            ->where('no_ayat', $noAyat)
            ->where('year', $currentYear)
            ->first();

        $quarters = ($this->distribute)($total, $startQ, $target);

        $record = DistrictAdditionalTarget::query()->updateOrCreate(
            ['district_id' => $district->id, 'no_ayat' => $noAyat, 'year' => $currentYear],
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

        Cache::forget("monitoring:district_additional:{$district->id}:{$currentYear}");

        return $record;
    }
}
