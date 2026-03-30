<?php

namespace App\Actions\Simpadu;

use App\Models\District;
use App\Models\TaxType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncSimpaduReferencesAction
{
    public function __invoke(): array
    {
        $results = [
            'districts' => ['created' => 0, 'updated' => 0],
            'tax_types' => ['created' => 0, 'updated' => 0],
        ];

        // 1. Sync Districts
        $simpaduDistricts = DB::connection('simpadunew')
            ->table('ref_kecamatan')
            ->get();

        foreach ($simpaduDistricts as $sDistrict) {
            $district = District::updateOrCreate(
                ['simpadu_code' => $sDistrict->KD_KECAMATAN],
                [
                    'name' => $sDistrict->NM_KECAMATAN,
                    'code' => 'KEC-' . Str::slug($sDistrict->NM_KECAMATAN),
                ]
            );

            if ($district->wasRecentlyCreated) {
                $results['districts']['created']++;
            } else {
                $results['districts']['updated']++;
            }
        }

        // 2. Sync Tax Types (Base Categories / Induk)
        $simpaduTaxTypes = DB::connection('simpadunew')
            ->table('ref_anggaran')
            ->where('jenis_ang', '00')
            ->where('klas_ang', '00')
            ->where('tahun_ang', date('Y'))
            ->get();

        foreach ($simpaduTaxTypes as $sTax) {
            $taxType = TaxType::updateOrCreate(
                ['simpadu_code' => $sTax->noayat_ang],
                [
                    'name' => $sTax->nama,
                    'code' => 'TAX-' . $sTax->noayat_ang,
                ]
            );

            if ($taxType->wasRecentlyCreated) {
                $results['tax_types']['created']++;
            } else {
                $results['tax_types']['updated']++;
            }

            // Sync Sub-categories for this Tax Type
            $this->syncSubCategories($taxType, $sTax->noayat_ang, $results);
        }

        return $results;
    }

    private function syncSubCategories(TaxType $parent, string $noayatAng, array &$results): void
    {
        $subCategories = DB::connection('simpadunew')
            ->table('ref_anggaran')
            ->where('noayat_ang', $noayatAng)
            ->where('jenis_ang', '!=', '00')
            ->where('klas_ang', '00')
            ->where('tahun_ang', date('Y'))
            ->get();

        foreach ($subCategories as $sSub) {
            $subCode = $noayatAng . '-' . $sSub->jenis_ang;
            $subTax = TaxType::updateOrCreate(
                ['simpadu_code' => $subCode],
                [
                    'name' => $sSub->nama,
                    'code' => 'TAX-' . $subCode,
                    'parent_id' => $parent->id,
                ]
            );

            if ($subTax->wasRecentlyCreated) {
                $results['tax_types']['created']++;
            } else {
                $results['tax_types']['updated']++;
            }
        }
    }
}
