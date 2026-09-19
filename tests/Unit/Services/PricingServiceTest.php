<?php

namespace Tests\Unit\Services;

use App\Models\Listing;
use App\Models\ManualReview;
use App\Services\AiService;
use App\Services\PriceCalculator;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_listing_updates_category_and_condition(): void
    {
        $listing = Listing::factory()->create([
            'description' => 'A working TV',
            'category' => null,
            'condition' => null,
        ]);

        $aiServiceMock = $this->createMock(AiService::class);
        $aiServiceMock->method('recognizeItem')->willReturn([
            'category' => 'Electronics',
            'condition' => 'Good',
            'brand' => 'Samsung',
            'model' => null,
            'detected_defects' => [],
            'confidence' => 0.95,
        ]);

        $calculatorMock = $this->createMock(PriceCalculator::class);
        $calculatorMock->method('calculateSuggestedPrice')->willReturn(8000.00);

        $service = new PricingService($aiServiceMock, $calculatorMock);
        $service->processListing($listing, 'http://example.com/image.jpg');

        $listing->refresh();
        $this->assertEquals('Electronics', $listing->category);
        $this->assertEquals('Good', $listing->condition);
        $this->assertEquals(8000.00, $listing->suggested_price);
    }

    public function test_process_listing_creates_manual_review_when_no_market_data(): void
    {
        $listing = Listing::factory()->create(['description' => 'A rare item']);

        $aiServiceMock = $this->createMock(AiService::class);
        $aiServiceMock->method('recognizeItem')->willReturn([
            'category' => 'RareItem',
            'condition' => 'Good',
            'brand' => null,
            'model' => null,
            'detected_defects' => [],
            'confidence' => 0.8,
        ]);

        $calculatorMock = $this->createMock(PriceCalculator::class);
        $calculatorMock->method('calculateSuggestedPrice')->willReturn(null);

        $service = new PricingService($aiServiceMock, $calculatorMock);
        $service->processListing($listing, 'http://example.com/image.jpg');

        $listing->refresh();
        $this->assertEquals('under_review', $listing->status);
        $this->assertTrue(ManualReview::where('listing_id', $listing->id)->exists());
    }
}
