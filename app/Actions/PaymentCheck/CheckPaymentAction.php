<?php

namespace App\Actions\PaymentCheck;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckPaymentAction
{
    private const TAX_TYPES = [
        'hotel' => '41101',
        'restoran' => '41102',
        'hiburan' => '41103',
        'reklame' => '41104',
        'ppj' => '41105',
        'parkir' => '41107',
        'at' => '41108',
        'minerba' => '41109',
        'bphtb' => '41113',
    ];

    public function __invoke(
        string $npwpd,
        string $tahun,
        string $jenisPajak,
        bool $npwpdLama = false,
        ?string $namaWp = null,
    ): array {
        $jenisPajak = strtolower($jenisPajak);

        if (! array_key_exists($jenisPajak, self::TAX_TYPES)) {
            return ['error' => 'Jenis pajak tidak valid.'];
        }

        $ayat = self::TAX_TYPES[$jenisPajak];

        // Verifikasi nama WP dari data lokal
        if ($namaWp && ! $this->verifyNamaWp($npwpd, $namaWp)) {
            return ['error' => 'NPWPD dan nama wajib pajak tidak cocok.'];
        }

        $rows = $this->queryLocal($npwpd, $tahun, $ayat);

        if ($rows->isEmpty()) {
            return ['data' => [], 'summary' => null];
        }

        $items = $rows->map(fn ($row) => $this->formatRow((array) $row))->values();

        return [
            'npwpd' => $items->first()['npwpd'],
            'nama_op' => $items->first()['nama_op'],
            'jenis_pajak' => $jenisPajak,
            'tahun' => $tahun,
            'data' => $items,
            'summary' => [
                'total_sptpd' => $items->sum('jml_sptpd'),
                'total_denda' => $items->sum('sisa_denda'),
                'total_bayar_pokok' => $items->sum('bayar_pokok'),
                'total_bayar_denda' => $items->sum('bayar_denda'),
                'total_sisa' => $items->sum('sisa'),
            ],
        ];
    }

    /**
     * Verifikasi nama WP dari tabel lokal simpadu_tax_payers.
     */
    private function verifyNamaWp(string $npwpd, string $namaWp): bool
    {
        $row = DB::table('simpadu_tax_payers')
            ->where('npwpd', $npwpd)
            ->value('nm_wp');

        if ($row === null) {
            return false;
        }

        $normalize = fn (string $s) => strtoupper(preg_replace('/\s+/', '', trim($s)));

        if ($normalize($row) === $normalize($namaWp)) {
            return true;
        }

        // Cocokkan tiap bagian yang dipisah "/"
        foreach (explode('/', $row) as $part) {
            if ($normalize($part) === $normalize($namaWp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Query data pembayaran dari tabel lokal simpadu_tax_payers.
     * Setiap baris = 1 bulan (masa pajak).
     */
    private function queryLocal(string $npwpd, string $tahun, string $ayat): Collection
    {
        return DB::table('simpadu_tax_payers as tp')
            ->leftJoin('simpadu_sptpd_reports as sr', function ($join) {
                $join->on('sr.npwpd', '=', 'tp.npwpd')
                    ->on('sr.nop', '=', 'tp.nop')
                    ->on('sr.year', '=', 'tp.year')
                    ->on('sr.month', '=', 'tp.month');
            })
            ->where('tp.npwpd', $npwpd)
            ->where('tp.ayat', $ayat)
            ->where('tp.year', $tahun)
            ->orderBy('tp.month')
            ->select([
                'tp.npwpd',
                'tp.nop',
                'tp.nm_op as nama_op',
                'tp.month',
                'tp.year',
                'tp.total_ketetapan',
                'tp.total_bayar',
                'tp.total_tunggakan',
                'tp.status',
                'sr.tgl_lapor',
                'sr.masa_pajak',
                'sr.jml_lapor',
            ])
            ->get();
    }

    private function formatRow(array $row): array
    {
        $ketetapan = (float) ($row['total_ketetapan'] ?? 0);
        $bayar = (float) ($row['total_bayar'] ?? 0);
        $tunggakan = (float) ($row['total_tunggakan'] ?? 0);
        $jmlLapor = (float) ($row['jml_lapor'] ?? 0);

        // Masa pajak: format YYYY-MM dari year+month
        $masaPajak = $row['masa_pajak']
            ?? sprintf('%d-%02d', $row['year'], $row['month']);

        return [
            'npwpd' => $row['npwpd'],
            'nop' => $row['nop'],
            'nama_op' => $row['nama_op'],
            'kohir' => null, // tidak tersedia di lokal
            'masa_awal' => $masaPajak,
            'jatuh_tempo' => null,
            'tgl_bayar' => $row['tgl_lapor'] ?? null,
            'jml_sptpd' => (int) $jmlLapor ?: (int) $ketetapan,
            'bayar_pokok' => (int) $bayar,
            'bayar_denda' => 0,
            'sisa_pokok' => (int) $tunggakan,
            'sisa_denda' => 0,
            'sisa' => (int) $tunggakan,
            'keterangan' => $row['status'] === '1' ? 'Lunas' : ($tunggakan > 0 ? 'Belum Lunas' : null),
        ];
    }
}
