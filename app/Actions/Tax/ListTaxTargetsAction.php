<?php

namespace App\Actions\Tax;

use App\Models\TaxTarget;
use App\Models\TaxType;
use App\Actions\Tax\GenerateTaxDashboardAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListTaxTargetsAction
{
    public function __invoke(?string $search = null, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');

        // Use the same action as the dashboard for 100% consistency
        $generateDashboard = app(GenerateTaxDashboardAction::class);
        $result = $generateDashboard(year: $year, search: $search);

        $availableYears = TaxTarget::query()
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
