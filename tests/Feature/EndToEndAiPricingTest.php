<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ManualReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndAiPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_pricing_flow_seller_accepts(): void
    {
        Queue::fake();
        Storage::fake('public');

        // 1. Seller creates listing
        $seller = User::factory()->create();
        $file = UploadedFile::fake()->create('tv.jpg', 100, 'image/jpeg');

        $createResponse = $this->actingAs($seller)->postJson('/listings', [
            'type' => 'sell',
            'title' => 'Samsung 43" TV',
            'description' => 'Works perfectly',
            'image' => $file,
        ]);

        $this->assertEquals(201, $createResponse->status());
        $listingId = $createResponse->json('listing_id');

        // 2. AI processes (simulate)
        $listing = Listing::find($listingId);
        $listing->update([
            'category' => 'Electronics',
            'condition' => 'Good',
            'suggested_price' => 28000.00,
            'processing_status' => 'completed',
        ]);

        // 3. Seller accepts price
        $acceptResponse = $this->actingAs($seller)->putJson("/listings/{$listingId}/accept-price");

        $this->assertEquals(200, $acceptResponse->status());
        $listing->refresh();
        $this->assertEquals(28000.00, $listing->final_price);
        $this->assertEquals('available', $listing->status);
    }

    public function test_complete_pricing_flow_admin_reviews(): void
    {
        // 1. Create listing under review
        $seller = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'category' => 'RareAntique',
            'condition' => 'Excellent',
            'suggested_price' => null,
            'status' => 'under_review',
        ]);

        ManualReview::create([
            'listing_id' => $listing->id,
            'status' => 'pending',
            'notes' => 'No market data for this category',
        ]);

        // 2. Admin views queue
        $admin = User::factory()->create(['role' => 'admin']);
        $queueResponse = $this->actingAs($admin)->getJson('/admin/manual-reviews');
        $this->assertEquals(200, $queueResponse->status());

        // 3. Admin approves and sets price
        $approveResponse = $this->actingAs($admin)->putJson(
            "/admin/manual-reviews/{$listing->manualReview->id}/approve",
            [
                'final_price' => 15000.00,
                'notes' => 'Comparable to similar antiques',
            ]
        );

        $this->assertEquals(200, $approveResponse->status());
        $listing->refresh();
        $this->assertEquals(15000.00, $listing->final_price);
        $this->assertEquals('available', $listing->status);
    }

    public function test_super_admin_price_override_flow(): void
    {
        // 1. Create available listing
        $seller = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $seller->id,
            'status' => 'available',
            'final_price' => 8000.00,
        ]);

        // 2. Super admin overrides price
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $overrideResponse = $this->actingAs($superAdmin)->postJson(
            "/admin/listings/{$listing->id}/override-price",
            [
                'new_price' => 7500.00,
                'reason' => 'Market adjustment due to bulk order',
            ]
        );

        $this->assertEquals(200, $overrideResponse->status());
        $listing->refresh();
        $this->assertEquals(7500.00, $listing->final_price);

        // 3. Super admin views audit trail
        $historyResponse = $this->actingAs($superAdmin)->getJson(
            "/admin/listings/{$listing->id}/price-history"
        );

        $this->assertEquals(200, $historyResponse->status());
        $this->assertCount(1, $historyResponse->json());
    }
}
