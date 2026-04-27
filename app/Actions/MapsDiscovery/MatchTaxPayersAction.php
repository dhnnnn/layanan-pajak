<?php

namespace App\Actions\MapsDiscovery;

use App\Models\SimpaduTaxPayer;
use Illuminate\Support\Collection;

class MatchTaxPayersAction
{
    /**
     * Jumlah kata bermakna minimum yang harus cocok.
     */
    private const MIN_MATCHING_WORDS = 2;

    /**
     * Minimum panjang kata agar dianggap bermakna untuk matching.
     */
    private const MIN_WORD_LENGTH = 4;

    /**
     * Kata-kata umum yang diabaikan (preposisi, lokasi, entitas umum).
     *
     * @var list<string>
     */
    private const STOP_WORDS = [
        'hotel', 'restoran', 'restaurant', 'cafe', 'kafe', 'warung', 'rumah',
        'makan', 'toko', 'salon', 'karaoke', 'villa', 'resort', 'guest',
        'house', 'homestay', 'kost', 'losmen', 'penginapan', 'pondok',
        'near', 'dekat', 'baru', 'lama', 'the', 'and', 'dan', 'atau',
        'jalan', 'raya', 'desa', 'kecamatan', 'kabupaten',
        'pasuruan', 'jawa', 'timur', 'indonesia',
        'syariah', 'budget', 'express', 'life', 'oyo',
    ];

    private ?Collection $districtCache = null;

    /**
     * Cocokkan hasil crawling dengan data WP terdaftar.
     *
     * Strategi: untuk setiap crawl result, ambil kata-kata bermakna dari nama,
     * lalu query DB langsung dengan LIKE untuk cari WP yang mengandung kata tersebut.
     * Ini jauh lebih cepat daripada load semua WP ke memory.
     */
    public function __invoke(Collection $crawlResults, ?string $ayat = null, ?string $kdKecamatan = null): Collection
    {
        return $crawlResults->map(function (array $result) use ($ayat, $kdKecamatan): array {
            $parsedDistrict = $this->parseDistrictFromAddress($result['subtitle'] ?? '');

            $match = $this->findBestMatch($result['title'], $ayat, $kdKecamatan);

            return array_merge($result, [
                'status' => $match ? 'terdaftar' : 'potensi_baru',
                'matched_npwpd' => $match['npwpd'] ?? null,
                'matched_name' => $match['name'] ?? null,
                'similarity_score' => $match['score'] ?? 0,
                'matching_words' => $match['word_count'] ?? 0,
                'parsed_district' => $parsedDistrict,
            ]);
        });
    }

    /**
     * Cari WP terbaik yang cocok dengan nama dari Maps.
     *
     * Strategi: query DB dengan LIKE per kata bermakna, lalu verifikasi
     * jumlah kata yang benar-benar cocok di PHP.
     *
     * @return array{npwpd: string, name: string, score: float, word_count: int}|null
     */
    private function findBestMatch(string $crawlTitle, ?string $ayat, ?string $kdKecamatan): ?array
    {
        $words = $this->extractWords($crawlTitle);

        if (count($words) < 1) {
            return null;
        }

        // Query DB: cari WP yang mengandung minimal 1 kata dari nama Maps
        // MySQL akan filter, bukan PHP — jauh lebih cepat
        $candidates = SimpaduTaxPayer::query()
            ->when($ayat, fn ($q) => $q->where('ayat', $ayat))
            ->when($kdKecamatan, fn ($q) => $q->where('kd_kecamatan', $kdKecamatan))
            ->where(function ($q) use ($words): void {
                foreach ($words as $word) {
                    $q->orWhere('nm_wp', 'LIKE', "%{$word}%")
                        ->orWhere('nm_op', 'LIKE', "%{$word}%");
                }
            })
            ->limit(50) // Batasi kandidat agar tidak terlalu banyak
            ->get(['npwpd', 'nm_wp', 'nm_op']);

        if ($candidates->isEmpty()) {
            return null;
        }

        // Verifikasi: hitung kata yang benar-benar cocok
        $bestMatch = null;
        $bestWordCount = 0;

        foreach ($candidates as $wp) {
            $wpWords = $this->extractWords($wp->nm_wp ?? '');
            $opWords = $this->extractWords($wp->nm_op ?? '');

            $matchCountWp = $this->countExactMatchingWords($words, $wpWords);
            $matchCountOp = $this->countExactMatchingWords($words, $opWords);
            $matchCount = max($matchCountWp, $matchCountOp);

            if ($matchCount > $bestWordCount) {
                $bestWordCount = $matchCount;
                $bestMatch = $wp;
            }
        }

        if ($bestWordCount < self::MIN_MATCHING_WORDS) {
            return null;
        }

        $totalWords = max(count($words), 1);
        $score = round($bestWordCount / $totalWords, 4);

        return [
            'npwpd' => $bestMatch->npwpd,
            'name' => $bestMatch->nm_wp,
            'score' => $score,
            'word_count' => $bestWordCount,
        ];
    }

    /**
     * Pecah string menjadi kata-kata bermakna: lowercase, tanpa stop words,
     * minimal MIN_WORD_LENGTH karakter.
     *
     * @return list<string>
     */
    private function extractWords(string $text): array
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', strtolower($text));
        $allWords = preg_split('/\s+/', trim($cleaned ?? ''), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            $allWords,
            fn (string $word): bool => mb_strlen($word) >= self::MIN_WORD_LENGTH
                && ! in_array($word, self::STOP_WORDS, true),
        ));
    }

    /**
     * Hitung kata yang exact match antara dua set.
     * Hanya exact match — tidak ada substring matching.
     */
    private function countExactMatchingWords(array $wordsA, array $wordsB): int
    {
        if (empty($wordsA) || empty($wordsB)) {
            return 0;
        }

        $setB = array_flip($wordsB);
        $count = 0;

        foreach ($wordsA as $word) {
            if (isset($setB[$word])) {
                $count++;
                unset($setB[$word]); // Satu kata hanya dihitung sekali
            }
        }

        return $count;
    }

    /**
     * Parse nama kecamatan dari alamat Google Maps.
     */
    private function parseDistrictFromAddress(string $address): ?string
    {
        if (preg_match('/(?:kec(?:amatan)?\.?\s+)([a-zA-Z\s]+?)(?:,|\d|$)/iu', $address, $matches)) {
            $district = trim($matches[1]);
            $district = preg_replace('/\b(?:kabupaten|kab|pasuruan|jawa|timur)\b/i', '', $district);

            return trim($district) ?: null;
        }

        return null;
    }
}
