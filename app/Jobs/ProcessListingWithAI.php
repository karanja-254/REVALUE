<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Services\PricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessListingWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public string $imageInput,
        public string $mimeType = 'image/jpeg',
    ) {}

    public function handle(PricingService $pricingService): void
    {
        try {
            $pricingService->processListing($this->listing, $this->imageInput, $this->mimeType);
        } catch (\Exception $e) {
            \Log::error('ProcessListingWithAI failed', [
                'listing_id' => $this->listing->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->listing->update(['processing_status' => 'failed']);
        \Log::error('Job failed permanently', ['listing_id' => $this->listing->id]);
    }
}
