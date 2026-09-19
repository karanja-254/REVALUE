<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\PriceOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_override_price(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $listing = Listing::factory()->create([
            'status' => 'available',
            'final_price' => 8000.00,
        ]);

        $response = $this->actingAs($superAdmin)->postJson("/admin/listings/{$listing->id}/override-price", [
            'new_price' => 7500.00,
            'reason' => 'Market adjustment',
        ]);

        $response->assertStatus(200);
        $listing->refresh();
        $this->assertEquals(7500.00, $listing->final_price);
        $this->assertTrue(PriceOverride::where('listing_id', $listing->id)->exists());
    }

    public function test_admin_cannot_override_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = Listing::factory()->create(['status' => 'available']);

        $response = $this->actingAs($admin)->postJson("/admin/listings/{$listing->id}/override-price", [
            'new_price' => 7500.00,
            'reason' => 'Market adjustment',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_price_override_history(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $listing = Listing::factory()->create();
        PriceOverride::factory()->create(['listing_id' => $listing->id]);

        $response = $this->actingAs($superAdmin)->getJson("/admin/listings/{$listing->id}/price-history");

        $response->assertStatus(200);
    }

    public function test_super_admin_can_override_to_ksh_5_for_demo(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $listing = Listing::factory()->create([
            'status' => 'available',
            'final_price' => 8000.00,
        ]);

        $response = $this->actingAs($superAdmin)->postJson("/admin/listings/{$listing->id}/override-price", [
            'new_price' => 5.00,
            'reason' => 'Paystack hackathon demo',
        ]);

        $response->assertStatus(200);
        $listing->refresh();
        $this->assertEquals(5.00, $listing->final_price);
    }
}
