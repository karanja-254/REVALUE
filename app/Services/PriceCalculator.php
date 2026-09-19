<?php

namespace App\Services;

use App\Models\JijiMarketData;

class PriceCalculator
{
    /**
     * Get median price from Jiji market data for category/condition
     *
     * @return float|null Median price or null if no data exists
     */
    public function calculateMedianPrice(string $category, string $condition): float|null
    {
        $prices = JijiMarketData::where('category', $category)
            ->where('condition', $condition)
            ->orderBy('price')
            ->pluck('price')
            ->toArray();

        if (empty($prices)) {
            return null;
        }

        $count = count($prices);
        $mid = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($prices[$mid - 1] + $prices[$mid]) / 2;
        }

        return (float) $prices[$mid];
    }

    /**
     * Apply ReValue pricing formula: median * 0.85
     * Fixed multiplier for deterministic pricing and replicability
     */
    public function applySuggestedPriceFormula(float $medianPrice): float
    {
        $multiplier = 0.85;
        return round($medianPrice * $multiplier, 2);
    }

    /**
     * Calculate suggested price (or null if no market data)
     */
    public function calculateSuggestedPrice(string $category, string $condition): float|null
    {
        $median = $this->calculateMedianPrice($category, $condition);

        if ($median === null) {
            return null;
        }

        return $this->applySuggestedPriceFormula($median);
    }
}
