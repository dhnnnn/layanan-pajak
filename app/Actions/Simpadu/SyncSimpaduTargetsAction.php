<?php

namespace App\Actions\Simpadu;

use App\Models\SimpaduTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSimpaduTargetsAction
{
    /**
     * Sync budget targets from Simpadu to local cache.
     *
     * Sumber data (urutan prioritas):
     * 1. m_target_anggaran          — tabel utama (biasanya tahun berjalan, APBD Murni)
     * 2. m_target_anggaran_old      — tabel historis APBD Murni tahun sebelumnya
     * 3. m_target_anggaran_{year}   — tabel per-tahun (APBD Perubahan, fallback)
     * 4. ref_anggaran_backup*       — backup tanpa tribulan pct (fallback terakhir)
     *
     * Prioritas APBD Murni > APBD Perubahan agar konsisten antar tahun.
     */
    public function __invoke(int $year): array
    {
        Log::info("Starting Simpadu Target Sync for year: {$year}");

        $simpaduTargets = $this->fetchFromMainTable($year)
            ?? $this->fetchFromOldTable($year)
            ?? $this->fetchFromYearTable($year)
            ?? $this->fetchFromRefBackup($year)
            ?? collect();

        $rows = [];
        foreach ($simpaduTargets as $sTarget) {
            $rows[] = [
                'no_ayat'     => (string) $sTarget->no_ayat,
                'year'        => $year,
                'keterangan'  => $sTarget->keterangan,
                'total_target' => $sTarget->target_anggaran,
                'q1_pct'      => $sTarget->tribulan_1 ?? 25.00,
                'q2_pct'      => $sTarget->tribulan_2 ?? 50.00,
                'q3_pct'      => $sTarget->tribulan_3 ?? 75.00,
                'q4_pct'      => $sTarget->tribulan_4 ?? 100.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (! empty($rows)) {
            SimpaduTarget::upsert(
                $rows,
                ['no_ayat', 'year'],
                ['keterangan', 'total_target', 'q1_pct', 'q2_pct', 'q3_pct', 'q4_pct', 'updated_at']
            );
        }

        Log::info("Target Sync done: " . count($rows) . " records from year {$year}");

        return ['count' => count($rows)];
    }

    private function fetchFromMainTable(int $year): ?\Illuminate\Support\Collection
    {
        $rows = DB::connection('simpadunew')
            ->table('m_target_anggaran')
            ->where('thn_anggaran', $year)
            ->get();

        return $rows->isNotEmpty() ? $rows : null;
    }

    private function fetchFromYearTable(int $year): ?\Illuminate\Support\Collection
    {
        $table = "m_target_anggaran_{$year}";
        $exists = DB::connection('simpadunew')->select("SHOW TABLES LIKE '{$table}'");

        if (empty($exists)) {
            return null;
        }

        $rows = DB::connection('simpadunew')
            ->table($table)
            ->where('thn_anggaran', $year)
            ->get();

        return $rows->isNotEmpty() ? $rows : null;
    }

    private function fetchFromOldTable(int $year): ?\Illuminate\Support\Collection
    {
        $rows = DB::connection('simpadunew')
            ->table('m_target_anggaran_old')
            ->where('thn_anggaran', $year)
            ->get();

        return $rows->isNotEmpty() ? $rows : null;
    }

    private function fetchFromRefBackup(int $year): ?\Illuminate\Support\Collection
    {
        $backupTables = [
            'ref_anggaran',
            'ref_anggaran_backup10072025',
            'ref_anggaran_backup01012026',
        ];

        foreach ($backupTables as $table) {
            $exists = DB::connection('simpadunew')->select("SHOW TABLES LIKE '{$table}'");
            if (empty($exists)) {
                continue;
            }

            // Ambil baris dengan nilai terbesar per ayat (baris induk selalu nilai terbesar)
            $rows = DB::connection('simpadunew')
                ->table($table)
                ->selectRaw("
                    noayat_ang as no_ayat,
                    nama as keterangan,
                    tahun_ang as thn_anggaran,
                    anggaran_ang as target_anggaran,
                    NULL as tribulan_1,
                    NULL as tribulan_2,
                    NULL as tribulan_3,
                    NULL as tribulan_4
                ")
                ->where('tahun_ang', (string) $year)
                ->whereIn('noayat_ang', ['41101','41102','41103','41104','41105','41107','41108','41111','41112','41113','41120','41121'])
                ->where('anggaran_ang', '>', 0)
                ->whereRaw("
                    anggaran_ang = (
                        SELECT MAX(r2.anggaran_ang)
                        FROM {$table} r2
                        WHERE r2.noayat_ang = {$table}.noayat_ang
                          AND r2.tahun_ang = {$table}.tahun_ang
                          AND r2.anggaran_ang > 0
                    )
                ")
                ->orderBy('noayat_ang')
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        return null;
    }
}
