<?php

namespace Tests\Unit\Models;

use App\Models\JijiMarketData;
use Tests\TestCase;

class JijiMarketDataTest extends TestCase
{
    public function test_jiji_market_data_fillable(): void
    {
        $data = JijiMarketData::make([
            'category' => 'Electronics',
            'condition' => 'Good',
            'price' => 5000.00,
            'source_url' => 'https://jiji.ke/item/123',
            'scraped_at' => now(),
        ]);

        $this->assertEquals('Electronics', $data->category);
        $this->assertEquals('Good', $data->condition);
        $this->assertEquals(5000.00, $data->price);
    }
}
