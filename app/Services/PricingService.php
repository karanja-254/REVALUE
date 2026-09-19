<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ManualReview;
use Exception;

class PricingService
{
    public function __construct(
        private AiService $aiService,
        private PriceCalculator $priceCalculator,
    ) {}

    /**
     * Process listing: AI recognition + pricing logic
     * Updates listing with AI results and suggests price or queues for manual review
     */
    public function processListing(Listing $listing, string $imageInput): void
    {
        $listing->update(['processing_status' => 'processing']);

        try {
            // Step 1: AI recognition
            $aiResult = $this->aiService->recognizeItem($imageInput, $listing->description ?? '');

            // Step 2: Update listing with AI results
            $listing->update([
                'category' => $aiResult['category'] ?? null,
                'condition' => $aiResult['condition'] ?? null,
                'ai_metadata' => $aiResult,
            ]);

            // Step 3: Calculate suggested price
            $suggestedPrice = $this->priceCalculator->calculateSuggestedPrice(
                $aiResult['category'],
                $aiResult['condition']
            );

            if ($suggestedPrice === null) {
                // No market data — queue for manual review
                ManualReview::create([
                    'listing_id' => $listing->id,
                    'status' => 'pending',
                    'notes' => 'No Jiji market data for ' . $aiResult['category'] . ' / ' . $aiResult['condition'],
                ]);
                $listing->update(['status' => 'under_review', 'processing_status' => 'completed']);
                return;
            }

            // Step 4: Update suggested price
            $listing->update(['suggested_price' => $suggestedPrice, 'processing_status' => 'completed']);
        } catch (Exception $e) {
            // AI failure — queue for manual review
            ManualReview::create([
                'listing_id' => $listing->id,
                'status' => 'pending',
                'notes' => 'AI processing failed: ' . $e->getMessage(),
            ]);
            $listing->update(['status' => 'under_review', 'processing_status' => 'failed']);
        }
    }
}
