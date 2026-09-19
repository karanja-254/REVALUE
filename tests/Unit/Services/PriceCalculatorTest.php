<?php

namespace Tests\Unit\Services;

use App\Models\JijiMarketData;
use App\Services\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }

    public function test_calculate_median_price_with_odd_count(): void
    {
        JijiMarketData::factory()->create(['category' => 'Electronics', 'condition' => 'Good', 'price' => 5000]);
        JijiMarketData::factory()->create(['category' => 'Electronics', 'condition' => 'Good', 'price' => 6000]);
        JijiMarketData::factory()->create(['category' => 'Electronics', 'condition' => 'Good', 'price' => 7000]);

        $median = $this->calculator->calculateMedianPrice('Electronics', 'Good');

        $this->assertEquals(6000, $median);
    }

    public function test_calculate_median_price_with_even_count(): void
    {
        JijiMarketData::factory()->create(['category' => 'Electronics', 'condition' => 'Good', 'price' => 5000]);
        JijiMarketData::factory()->create(['category' => 'Electronics', 'condition' => 'Good', 'price' => 7000]);

        $median = $this->calculator->calculateMedianPrice('Electronics', 'Good');

        $this->assertEquals(6000, $median);
    }

    public function test_calculate_median_price_returns_null_when_no_data(): void
    {
        $median = $this->calculator->calculateMedianPrice('NonExistent', 'Good');

        $this->assertNull($median);
    }

    public function test_apply_suggested_price_formula(): void
    {
        $suggested = $this->calculator->applySuggestedPriceFormula(10000);

        $this->assertGreaterThanOrEqual(8000, $suggested);
        $this->assertLessThanOrEqual(9000, $suggested);
    }

    public function test_calculate_suggested_price_returns_null_when_no_market_data(): void
    {
        $suggested = $this->calculator->calculateSuggestedPrice('NonExistent', 'Good');

        $this->assertNull($suggested);
    }
}
