<?php

namespace App\Actions\MapsDiscovery;

use App\Models\MapsDiscoveryResult;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetDiscoveryReportAction
{
    /**
     * Ambil data report discovery dengan filtering dan stats.
     *
     * @return array{results: LengthAwarePaginator, stats: array{total: int, terdaftar: int, potensi_baru: int, belum_dicek: int}, taxTypeCodes: Collection, taxTypeNames: Collection, districtNames: Collection, filters: array}
     */
    public function __invoke(Request $request, User $user): array
    {
        $query = MapsDiscoveryResult::query();

        // Kepala UPT: hanya lihat data dari kecamatan UPT-nya
        $allowedDistricts = null;
        if ($user->isKepalaUpt()) {
            $allowedDistricts = $user->upt()?->districts->pluck('name')->toArray() ?? [];
            if (! empty($allowedDistricts)) {
                $query->where(function ($q) use ($allowedDistricts): void {
                    foreach ($allowedDistricts as $name) {
                        $q->orWhere('district_name', $name);
                    }
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('tax_type_code')) {
            $query->where('tax_type_code', $request->input('tax_type_code'));
        }
        if ($request->filled('district_name')) {
            $query->where('district_name', $request->input('district_name'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('matched_name', 'like', "%{$search}%");
            });
        }

        $results = $query->orderByDesc('updated_at')->paginate(10)->withQueryString();

        // Stats juga harus sesuai scope user
        $statsQuery = MapsDiscoveryResult::query();
        if ($allowedDistricts !== null && ! empty($allowedDistricts)) {
            $statsQuery->where(function ($q) use ($allowedDistricts): void {
                foreach ($allowedDistricts as $name) {
                    $q->orWhere('district_name', $name);
                }
            });
        }

        $belumDicek = (clone $statsQuery)->where('status', 'belum_dicek')->count();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'terdaftar' => (clone $statsQuery)->where('status', 'terdaftar')->count(),
            'potensi_baru' => (clone $statsQuery)->where('status', 'potensi_baru')->count(),
            'belum_dicek' => $belumDicek,
        ];

        // Dropdown kecamatan: hanya yang sesuai scope
        $districtQuery = MapsDiscoveryResult::query()
            ->whereNotNull('district_name')
            ->distinct()
            ->orderBy('district_name');

        if ($allowedDistricts !== null && ! empty($allowedDistricts)) {
            $districtQuery->whereIn('district_name', $allowedDistricts);
        }

        $districtNames = $districtQuery->pluck('district_name');

        $taxTypeCodes = MapsDiscoveryResult::query()
            ->whereNotNull('tax_type_code')
            ->distinct()
            ->pluck('tax_type_code');

        $taxTypeNames = TaxType::query()
            ->whereIn('simpadu_code', $taxTypeCodes)
            ->pluck('name', 'simpadu_code');

        return [
            'results' => $results,
            'stats' => $stats,
            'taxTypeCodes' => $taxTypeCodes,
            'taxTypeNames' => $taxTypeNames,
            'districtNames' => $districtNames,
            'filters' => $request->only(['status', 'tax_type_code', 'district_name', 'search']),
        ];
    }
}
