<?php

namespace App\Actions\Tax;

class CalculateAchievementPercentageAction
{
    public function __invoke(
        float $totalRealization,
        float $targetAmount,
    ): float {
        if ($targetAmount <= 0) {
            return 0.0;
        }

        return round(($totalRealization / $targetAmount) * 100, 2);
    }
}
