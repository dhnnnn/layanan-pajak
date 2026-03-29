<?php

namespace App\Actions\Tax;

use App\Models\TaxRealization;
use App\Models\TaxRealizationDailyEntry;
use App\Models\UptComparison;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetEmployeeRealizationIndexAction
{
    /**
     * @return array{
     *     districts: Collection,
     *     realizations: LengthAwarePaginator,
     *     uptTarget: float,
     *     districtTotals: Collection
     * }
     */
    public function __invoke(User $user, int $year, ?string $search = null): array
    {
        $districts = $user->accessibleDistricts()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $realizations = TaxRealization::query()
            ->with(['taxType', 'district'])
            ->when(! $user->hasRole('admin'), function ($query) use ($user) {
                if ($user->hasRole('kepala_upt')) {
                    $query->whereHas('district.upts', fn ($q) => $q->where('upts.id', $user->upt_id));
                } else {
                    $query->where('user_id', $user->id);
                }
            })
            ->orderByDesc('year')
            ->orderBy('tax_type_id')
            ->paginate(15);

        $uptTarget = (float) UptComparison::query()
            ->where('upt_id', $user->upt_id)
            ->where('year', $year)
            ->sum('target_amount');

        $districtTotals = TaxRealizationDailyEntry::query()
            ->where('user_id', $user->id)
            ->whereYear('entry_date', $year)
            ->selectRaw('district_id, SUM(amount) as total')
            ->groupBy('district_id')
            ->pluck('total', 'district_id');

        return [
            'districts' => $districts,
            'realizations' => $realizations,
            'uptTarget' => $uptTarget,
            'districtTotals' => $districtTotals,
        ];
    }
}
