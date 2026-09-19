<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ManualReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManualReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_reviews(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $review = ManualReview::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->getJson('/admin/manual-reviews');

        $response->assertStatus(200);
    }

    public function test_admin_can_approve_review_and_set_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = Listing::factory()->create(['status' => 'under_review']);
        $review = ManualReview::factory()->create(['listing_id' => $listing->id, 'status' => 'pending']);

        $response = $this->actingAs($admin)->putJson("/admin/manual-reviews/{$review->id}/approve", [
            'final_price' => 9500.00,
            'notes' => 'Good condition, slightly below market',
        ]);

        $response->assertStatus(200);
        $listing->refresh();
        $this->assertEquals(9500.00, $listing->final_price);
        $this->assertEquals('available', $listing->status);
        $review->refresh();
        $this->assertEquals('reviewed', $review->status);
    }

    public function test_non_admin_cannot_approve_review(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $review = ManualReview::factory()->create();

        $response = $this->actingAs($user)->putJson("/admin/manual-reviews/{$review->id}/approve", [
            'final_price' => 9500.00,
        ]);

        $response->assertStatus(403);
    }
}
