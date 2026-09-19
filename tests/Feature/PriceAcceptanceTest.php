<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_accept_suggested_price(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'suggested_price' => 8000.00,
            'final_price' => null,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->putJson("/api/listings/{$listing->id}/accept-price");

        $response->assertStatus(200);
        $listing->refresh();
        $this->assertEquals(8000.00, $listing->final_price);
        $this->assertEquals('available', $listing->status);
    }

    public function test_seller_cannot_accept_price_without_suggestion(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'suggested_price' => null,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->putJson("/api/listings/{$listing->id}/accept-price");

        $response->assertStatus(422);
    }
}
