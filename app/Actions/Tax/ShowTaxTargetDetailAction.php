<?php

namespace App\Actions\Tax;

use App\Models\District;
use App\Models\SimpaduTaxPayerRealization;
use App\Models\TaxType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShowTaxTargetDetailAction
{
    public function __construct(
        private readonly GenerateTaxDashboardAction $generateDashboard,
    ) {}

    /**
     * @return array{
     *     taxType: TaxType,
     *     summary: array|null,
     *     payers: LengthAwarePaginator,
     *     districts: Collection<int, District>,
     * }
     */
    public function __invoke(TaxType $taxType, int $year, ?string $search = null, ?string $selectedDistrict = null): array
    {
        // Get summarized data for the header (Consistency with dashboard)
        $dashboard = ($this->generateDashboard)(year: $year);
        $summary = collect($dashboard['data'])->firstWhere('no_ayat', $taxType->simpadu_code);

        // Get all descendant IDs recursively to aggregate WP data
        $allTaxTypeIds = $this->getAllDescendantIds($taxType);

        $query = SimpaduTaxPayerRealization::query()
            ->select('npwpd', 'nm_wp', DB::raw('SUM(total_realization) as total_realization'), DB::raw('MAX(last_sync_at) as last_sync_at'))
            ->whereIn('tax_type_id', $allTaxTypeIds)
            ->where('year', $year);

        // Search Filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nm_wp', 'like', "%{$search}%")
                    ->orWhere('npwpd', 'like', "%{$search}%");
            });
        }

        // District Filter
        if ($selectedDistrict) {
            $query->where('kd_kecamatan', $selectedDistrict);
        }

        $payers = $query->groupBy('npwpd', 'nm_wp')
            ->orderByDesc('total_realization')
            ->paginate(15)
            ->withQueryString();

        $districts = District::query()->orderBy('name')->get();

        return [
            'taxType' => $taxType,
            'summary' => $summary,
            'payers' => $payers,
            'districts' => $districts,
        ];
    }

    private function getAllDescendantIds(TaxType $taxType): array
    {
        $ids = [$taxType->id];

        foreach ($taxType->children as $child) {
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }
}
