<?php

namespace App\Actions\MapsDiscovery;

use App\Models\SimpaduTaxPayer;
use Illuminate\Support\Collection;

class MatchTaxPayersAction
{
    private const SIMILARITY_THRESHOLD = 0.7;

    /**
     * Cocokkan hasil crawling dengan data WP terdaftar di simpadu_tax_payers.
     *
     * @param  Collection<int, array{title: string, subtitle: string, category: string, place_id: string, url: string, latitude: ?float, longitude: ?float}>  $crawlResults
     * @param  string|null  $ayat  Kode ayat pajak untuk filter WP
     * @param  string|null  $kdKecamatan  Kode kecamatan untuk filter WP
     * @return Collection<int, array{title: string, subtitle: string, category: string, place_id: string, url: string, latitude: ?float, longitude: ?float, status: string, matched_npwpd: ?string, matched_name: ?string, similarity_score: float}>
     */
    public function __invoke(Collection $crawlResults, ?string $ayat = null, ?string $kdKecamatan = null): Collection
    {
        $taxPayers = SimpaduTaxPayer::query()
            ->when($ayat, fn ($q) => $q->where('ayat', $ayat))
            ->when($kdKecamatan, fn ($q) => $q->where('kd_kecamatan', $kdKecamatan))
            ->get();

        return $crawlResults->map(function (array $result) use ($taxPayers): array {
            $bestNameScore = 0.0;
            $bestCombinedScore = 0.0;
            $matchedNpwpd = null;
            $matchedName = null;

            foreach ($taxPayers as $wp) {
                $nameScore = max(
                    $this->similarity($result['title'], $wp->nm_wp ?? ''),
                    $this->similarity($result['title'], $wp->nm_op ?? ''),
                );

                // Hanya pertimbangkan jika nama cukup mirip (> 0.5)
                if ($nameScore < 0.5) {
                    continue;
                }

                $addressScore = $this->similarity($result['subtitle'], $wp->almt_op ?? '');

                // Skor gabungan: nama lebih penting (70%) + alamat (30%)
                $combinedScore = ($nameScore * 0.7) + ($addressScore * 0.3);

                if ($combinedScore > $bestCombinedScore) {
                    $bestCombinedScore = $combinedScore;
                    $bestNameScore = $nameScore;
                    $matchedNpwpd = $wp->npwpd;
                    $matchedName = $wp->nm_wp;
                }
            }

            $isTerdaftar = $bestCombinedScore >= self::SIMILARITY_THRESHOLD;

            return array_merge($result, [
                'status' => $isTerdaftar ? 'terdaftar' : 'potensi_baru',
                'matched_npwpd' => $isTerdaftar ? $matchedNpwpd : null,
                'matched_name' => $isTerdaftar ? $matchedName : null,
                'similarity_score' => round($bestCombinedScore, 4),
            ]);
        });
    }

    /**
     * Hitung similarity score antara dua string menggunakan similar_text(), dinormalisasi ke 0.0 - 1.0.
     */
    private function similarity(string $a, string $b): float
    {
        if ($a === '' && $b === '') {
            return 0.0;
        }

        similar_text(strtolower($a), strtolower($b), $percent);

        return $percent / 100;
    }
}
