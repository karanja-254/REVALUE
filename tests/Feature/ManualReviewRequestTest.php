<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ManualReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualReviewRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_request_manual_review(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->putJson("/api/listings/{$listing->id}/request-review", [
            'reason' => 'I think the price is too low',
        ]);

        $response->assertStatus(200);
        $listing->refresh();
        $this->assertEquals('under_review', $listing->status);
        $this->assertTrue(ManualReview::where('listing_id', $listing->id)->exists());
    }

    public function test_seller_cannot_request_review_twice(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id]);
        ManualReview::create(['listing_id' => $listing->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->putJson("/api/listings/{$listing->id}/request-review", [
            'reason' => 'Another review',
        ]);

        $response->assertStatus(422);
    }
}
