<?php

namespace App\Services;

use App\Models\JijiMarketData;

/**
 * JijiScraperService - MVP Market Reference Data
 *
 * For the MVP hackathon, market pricing uses hardcoded reference data seeded from typical Jiji.ke categories/conditions.
 * Production should replace seedMarketData() with actual web scraping from Jiji.ke.
 */
class JijiScraperService
{
    /**
     * Seed market data with hardcoded Jiji-like pricing (MVP approach)
     * Production: Replace with actual Jiji.ke web scraping
     */
    public function seedMarketData(): void
    {
        $data = [
            ['category' => 'Electronics', 'condition' => 'Mint', 'price' => 45000],
            ['category' => 'Electronics', 'condition' => 'Mint', 'price' => 48000],
            ['category' => 'Electronics', 'condition' => 'Excellent', 'price' => 35000],
            ['category' => 'Electronics', 'condition' => 'Excellent', 'price' => 38000],
            ['category' => 'Electronics', 'condition' => 'Good', 'price' => 25000],
            ['category' => 'Electronics', 'condition' => 'Good', 'price' => 28000],
            ['category' => 'Electronics', 'condition' => 'Fair', 'price' => 15000],
            ['category' => 'Furniture', 'condition' => 'Mint', 'price' => 15000],
            ['category' => 'Furniture', 'condition' => 'Good', 'price' => 8000],
            ['category' => 'Furniture', 'condition' => 'Fair', 'price' => 3000],
            ['category' => 'Appliances', 'condition' => 'Good', 'price' => 12000],
            ['category' => 'Appliances', 'condition' => 'Fair', 'price' => 5000],
        ];

        // Clear old data and insert new
        JijiMarketData::truncate();

        foreach ($data as $item) {
            JijiMarketData::create([
                'category' => $item['category'],
                'condition' => $item['condition'],
                'price' => $item['price'],
                'source_url' => 'https://jiji.ke/seed-data',
                'scraped_at' => now(),
            ]);
        }
    }

    /**
     * For future: implement real Jiji scraping
     * This is a placeholder for MVP
     */
    public function scrapeJijiListings(): void
    {
        // TODO: Implement actual web scraping from Jiji.ke
        // For now, just refresh seed data
        $this->seedMarketData();
    }
}
