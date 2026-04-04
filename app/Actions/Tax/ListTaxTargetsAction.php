<?php

namespace App\Actions\Tax;

use App\Models\SimpaduTarget;

class ListTaxTargetsAction
{
    public function __invoke(?string $search = null, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');

        // Use the same action as the dashboard for 100% consistency
        $generateDashboard = app(GenerateTaxDashboardAction::class);
        $result = $generateDashboard(year: $year, search: $search);

        $availableYears = SimpaduTarget::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([$year]);
        }

        return [
            'taxTypes' => $result['data'],
            'availableYears' => $availableYears,
            'year' => $year,
        ];
    }
}
