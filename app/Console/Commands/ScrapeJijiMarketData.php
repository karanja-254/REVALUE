<?php

namespace App\Console\Commands;

use App\Services\JijiScraperService;
use Illuminate\Console\Command;

class ScrapeJijiMarketData extends Command
{
    protected $signature = 'jiji:scrape';

    protected $description = '[MVP] Refresh seeded market reference data (placeholder for live Jiji scraping)';

    public function handle(JijiScraperService $scraper): int
    {
        $this->info('Scraping Jiji market data...');

        try {
            $scraper->scrapeJijiListings();
            $this->info('Jiji data ingestion complete.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Scrape failed: ' . $e->getMessage());
            return 1;
        }
    }
}
