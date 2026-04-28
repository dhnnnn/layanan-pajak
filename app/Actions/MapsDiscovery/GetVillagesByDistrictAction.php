<?php

namespace App\Actions\MapsDiscovery;

use App\Models\District;
use App\Models\Village;
use Illuminate\Support\Collection;

class GetVillagesByDistrictAction
{
    /**
     * Ambil daftar desa/kelurahan berdasarkan district_id.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    public function __invoke(string $districtId): Collection
    {
        $district = District::find($districtId);
        if (! $district || ! $district->district_code) {
            return collect();
        }

        return Village::where('district_code', $district->district_code)
            ->orderBy('name')
            ->get(['code', 'name', 'type'])
            ->map(fn (Village $v): array => [
                'id' => $v->name,
                'name' => $v->name.($v->type === 'kelurahan' ? ' (Kel.)' : ''),
            ]);
    }
}
