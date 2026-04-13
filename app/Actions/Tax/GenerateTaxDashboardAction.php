<?php

namespace App\Actions\Tax;

use App\Models\SimpaduMonthlyRealization;
use App\Models\SimpaduTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GenerateTaxDashboardAction
{
    // Ayat yang masuk ke grup induk "Pajak (PBJT)"
    private const PBJT_AYAT = ['41101', '41102', '41103', '41105', '41107'];

    public function __construct(
        private readonly CalculateAchievementPercentageAction $calculateAchievementPercentage,
    ) {}

    public function __invoke(int $year, ?string $search = null): array
    {
        $cacheKey = "dashboard:tax:{$year}";

        $result = Cache::remember($cacheKey, now()->addHours(6), function () use ($year) {
            return $this->build($year);
        });

        if ($search) {
            $result['data'] = $result['data']->filter(
                fn ($i) => str_contains(strtolower($i['tax_type_name']), strtolower($search))
            )->values();
        }

        return $result;
    }

    private function build(int $year): array
    {
        // Targets dari lokal (synced dari m_target_anggaran simpadunew)
        $targets = SimpaduTarget::query()
            ->where('year', $year)
            ->orderBy('no_ayat')
            ->get()
            ->keyBy('no_ayat');

        // Realisasi dari lokal (synced dari pembayaran simpadunew)
        $realizations = SimpaduMonthlyRealization::query()
            ->where('year', $year)
            ->get()
            ->groupBy('ayat');

        // Tentukan quarter terakhir yang boleh ditampilkan:
        // Tampilkan quarter jika sudah ada data pembayaran di quarter tersebut,
        // ATAU jika quarter tersebut sudah selesai secara kalender.
        $currentMonth = (int) now()->month;
        $currentQuarter = match (true) {
            $currentMonth <= 3 => 1,
            $currentMonth <= 6 => 2,
            $currentMonth <= 9 => 3,
            default => 4,
        };

        $lastSyncedMonth = (int) SimpaduMonthlyRealization::query()
            ->where('year', $year)
            ->max('month');

        $lastSyncedQuarter = match (true) {
            $lastSyncedMonth <= 3 => 1,
            $lastSyncedMonth <= 6 => 2,
            $lastSyncedMonth <= 9 => 3,
            default => 4,
        };

        // Tampilkan sampai quarter yang ada datanya (tidak melebihi quarter berjalan)
        // Untuk tahun historis (sudah lewat), tampilkan semua 4 quarter
        $isPastYear = $year < (int) now()->year;
        $lastDataQuarter = $isPastYear ? 4 : min($lastSyncedQuarter, $currentQuarter);

        // Pass 1: pisahkan PBJT dan non-PBJT
        $pbjt = $this->emptyPbjt($year);
        $pbjt['_children'] = [];
        $nonPbjtItems = collect();

        foreach ($targets as $target) {
            $item = $this->buildItem($target, $realizations, $lastDataQuarter, $year);

            if (in_array((string) $target->no_ayat, self::PBJT_AYAT)) {
                $pbjt['target_total'] += $item['target_total'];
                $pbjt['total_realization'] += $item['total_realization'];
                foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                    $pbjt['targets'][$q] += $item['targets'][$q];
                    $pbjt['realizations'][$q] += $item['realizations'][$q];
                }
                $item['is_child'] = true;
                $pbjt['_children'][] = $item;
            } else {
                $item['is_child'] = false;
                $nonPbjtItems->push($item);
            }
        }

        // Pass 2: bangun $data — PBJT parent+children dulu, lalu non-PBJT
        $data = collect();
        if (! empty($pbjt['_children'])) {
            $data = $this->flushPbjt($data, $pbjt);
        }
        foreach ($nonPbjtItems as $item) {
            $data->push($item);
        }

        // Hitung ulang persentase untuk baris induk PBJT
        $data = $data->map(function (array $item): array {
            if ($item['is_parent'] ?? false) {
                foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                    $item['percentages'][$q] = ($this->calculateAchievementPercentage)(
                        $item['realizations'][$q],
                        $item['targets'][$q]
                    );
                }
                $item['more_less'] = $item['total_realization'] - $item['target_total'];
                $item['achievement_percentage'] = ($this->calculateAchievementPercentage)(
                    $item['total_realization'],
                    $item['target_total']
                );
            }

            return $item;
        });

        // Grand totals — hanya dari baris top-level (bukan child)
        $topLevel = $data->where('is_child', false);
        $grandTotalTarget = $topLevel->sum('target_total');
        $grandTotalRealization = $topLevel->sum('total_realization');

        $quarterTotals = collect(['q1', 'q2', 'q3', 'q4'])->mapWithKeys(function ($q) use ($topLevel) {
            $t = $topLevel->sum(fn ($i) => (float) $i['targets'][$q]);
            $r = $topLevel->sum(fn ($i) => (float) $i['realizations'][$q]);

            return [$q => [
                'target' => $t,
                'realization' => $r,
                'percentage' => ($this->calculateAchievementPercentage)($r, $t),
            ]];
        })->toArray();

        return [
            'data' => $data,
            'totals' => [
                'target' => $grandTotalTarget,
                'realization' => $grandTotalRealization,
                'more_less' => $grandTotalRealization - $grandTotalTarget,
                'percentage' => ($this->calculateAchievementPercentage)($grandTotalRealization, $grandTotalTarget),
                'quarters' => $quarterTotals,
            ],
        ];
    }

    private function buildItem(SimpaduTarget $target, Collection $realizations, int $lastDataQuarter, int $year): array
    {
        $ayatRealizations = $realizations->get((string) $target->no_ayat, collect());

        $byQuarter = $ayatRealizations->groupBy(fn ($r) => match (true) {
            (int) $r->month <= 3 => 'q1',
            (int) $r->month <= 6 => 'q2',
            (int) $r->month <= 9 => 'q3',
            default => 'q4',
        });

        $rq1 = (float) ($byQuarter->get('q1')?->sum('total_bayar') ?? 0);
        $rq2 = (float) ($byQuarter->get('q2')?->sum('total_bayar') ?? 0);
        $rq3 = (float) ($byQuarter->get('q3')?->sum('total_bayar') ?? 0);
        $rq4 = (float) ($byQuarter->get('q4')?->sum('total_bayar') ?? 0);

        $totalTarget = (float) $target->total_target;
        $tq1 = $totalTarget * ((float) $target->q1_pct / 100);
        $tq2 = $totalTarget * ((float) $target->q2_pct / 100);
        $tq3 = $totalTarget * ((float) $target->q3_pct / 100);
        $tq4 = $totalTarget * ((float) $target->q4_pct / 100);

        // Per-quarter, hanya tampilkan jika quarter tersebut sudah ada datanya
        $cq1 = $rq1;
        $cq2 = $lastDataQuarter >= 2 ? $rq2 : 0;
        $cq3 = $lastDataQuarter >= 3 ? $rq3 : 0;
        $cq4 = $lastDataQuarter >= 4 ? $rq4 : 0;

        $totalRealization = $rq1 + $rq2 + $rq3 + $rq4;

        return [
            'no_ayat' => $target->no_ayat,
            'tax_type_name' => $target->keterangan,
            'year' => $year,
            'is_parent' => false,
            'target_total' => $totalTarget,
            'targets' => ['q1' => $tq1, 'q2' => $tq2, 'q3' => $tq3, 'q4' => $tq4],
            'realizations' => ['q1' => $cq1, 'q2' => $cq2, 'q3' => $cq3, 'q4' => $cq4],
            'percentages' => [
                'q1' => ($this->calculateAchievementPercentage)($cq1, $tq1),
                'q2' => ($this->calculateAchievementPercentage)($cq2, $tq2),
                'q3' => ($this->calculateAchievementPercentage)($cq3, $tq3),
                'q4' => ($this->calculateAchievementPercentage)($cq4, $tq4),
            ],
            'total_realization' => $totalRealization,
            'more_less' => $totalRealization - $totalTarget,
            'achievement_percentage' => ($this->calculateAchievementPercentage)($totalRealization, $totalTarget),
        ];
    }

    private function emptyPbjt(int $year): array
    {
        return [
            'no_ayat' => '41100',
            'tax_type_name' => 'Pajak (PBJT)',
            'year' => $year,
            'is_parent' => true,
            'is_child' => false,
            'target_total' => 0.0,
            'targets' => ['q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0],
            'realizations' => ['q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0],
            'percentages' => ['q1' => 0.0, 'q2' => 0.0, 'q3' => 0.0, 'q4' => 0.0],
            'total_realization' => 0.0,
            'more_less' => 0.0,
            'achievement_percentage' => 0.0,
        ];
    }

    private function flushPbjt(Collection $data, array $pbjt): Collection
    {
        $children = $pbjt['_children'];
        unset($pbjt['_children']);
        $data->push($pbjt);
        foreach ($children as $child) {
            $data->push($child);
        }

        return $data;
    }
}
