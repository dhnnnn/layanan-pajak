<?php

namespace App\Actions\MapsDiscovery;

use App\Models\MapsDiscoveryResult;

class SyncDiscoveryResultsAction
{
    public function __construct(
        private MatchTaxPayersAction $matchAction,
    ) {}

    /**
     * Sinkronkan data crawling dengan database WP.
     * Matching dilakukan per batch (50 record) agar tidak timeout/OOM.
     *
     * @return array{message: string, synced: int, remaining: int}
     */
    public function __invoke(): array
    {
        $unsynced = MapsDiscoveryResult::where('status', 'belum_dicek')
            ->limit(50)
            ->get();

        if ($unsynced->isEmpty()) {
            return [
                'message' => 'Semua data sudah disinkronkan.',
                'synced' => 0,
                'remaining' => 0,
            ];
        }

        $synced = 0;

        // Group by tax_type_code untuk efisiensi query
        $grouped = $unsynced->groupBy('tax_type_code');

        foreach ($grouped as $ayat => $results) {
            $crawlCollection = $results->map(fn (MapsDiscoveryResult $r): array => [
                'title' => $r->title,
                'subtitle' => $r->subtitle ?? '',
                'category' => $r->category ?? '',
                'place_id' => $r->place_id ?? '',
                'url' => $r->url ?? '',
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'rating' => $r->rating,
                'reviews' => $r->reviews,
                'price_range' => $r->price_range,
            ]);

            $matched = ($this->matchAction)(collect($crawlCollection->values()), $ayat ?: null, null);

            foreach ($results->values() as $index => $dbResult) {
                $matchedItem = $matched->get($index);
                if (! $matchedItem) {
                    continue;
                }

                $dbResult->update([
                    'status' => $matchedItem['status'],
                    'matched_npwpd' => $matchedItem['matched_npwpd'],
                    'matched_name' => $matchedItem['matched_name'],
                    'similarity_score' => $matchedItem['similarity_score'] ?? 0,
                ]);
                $synced++;
            }
        }

        $remaining = MapsDiscoveryResult::where('status', 'belum_dicek')->count();

        return [
            'message' => "Berhasil sinkronkan {$synced} data.",
            'synced' => $synced,
            'remaining' => $remaining,
        ];
    }
}
