<?php

namespace Database\Factories;

use App\Models\JijiMarketData;
use Illuminate\Database\Eloquent\Factories\Factory;

class JijiMarketDataFactory extends Factory
{
    protected $model = JijiMarketData::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(['Electronics', 'Furniture', 'Appliances']),
            'condition' => $this->faker->randomElement(['Mint', 'Excellent', 'Good', 'Fair']),
            'price' => $this->faker->randomFloat(2, 1000, 50000),
            'source_url' => $this->faker->url(),
            'scraped_at' => now(),
        ];
    }
}
