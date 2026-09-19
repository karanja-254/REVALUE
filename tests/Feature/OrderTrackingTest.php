<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_view_their_order_tracking(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create(['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->get(route('tracking.show', $order))
            ->assertOk()
            ->assertSee('Live tracking');
    }

    public function test_buyer_sees_their_delivery_pin_once_the_item_is_collected(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'order_status' => Order::STATUS_PICKED_UP,
        ]);

        $this->actingAs($buyer)
            ->get(route('tracking.show', $order))
            ->assertOk()
            ->assertSee($order->delivery_pin);
    }

    public function test_seller_can_view_tracking_for_orders_on_their_listing(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->available()->create();
        $order = Order::factory()->for($listing)->paid()->create();

        $this->actingAs($seller)
            ->get(route('tracking.show', $order))
            ->assertOk();
    }

    public function test_unrelated_user_cannot_view_tracking(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('tracking.show', $order))
            ->assertForbidden();
    }

    public function test_tracking_index_only_lists_the_users_own_orders(): void
    {
        $buyer = User::factory()->create();
        $mine = Order::factory()->paid()->create(['buyer_id' => $buyer->id]);
        $theirs = Order::factory()->paid()->create();

        $this->actingAs($buyer)
            ->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('Order #'.$mine->id)
            ->assertDontSee('Order #'.$theirs->id);
    }

    public function test_locations_endpoint_returns_map_json_for_the_buyer(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'delivery_latitude' => -1.29,
            'delivery_longitude' => 36.82,
            'delivery_address' => 'Kilimani, Nairobi',
        ]);

        $this->actingAs($buyer)
            ->getJson(route('tracking.locations', $order))
            ->assertOk()
            ->assertJsonStructure(['stops', 'driver', 'center' => ['lat', 'lng']]);
    }

    public function test_seller_sees_pickup_location_picker_when_not_yet_shared(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->available()->create([
            'pickup_address' => null,
            'pickup_latitude' => null,
            'pickup_longitude' => null,
        ]);
        $order = Order::factory()->for($listing)->paid()->create();

        $this->actingAs($seller)
            ->get(route('tracking.show', $order))
            ->assertOk()
            ->assertSee('Share your pickup location')
            ->assertSee('Use my current location');
    }

    public function test_buyer_sees_delivery_location_picker_when_not_yet_shared(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create([
            'buyer_id' => $buyer->id,
            'delivery_address' => null,
            'delivery_latitude' => null,
            'delivery_longitude' => null,
        ]);

        $this->actingAs($buyer)
            ->get(route('tracking.show', $order))
            ->assertOk()
            ->assertSee('Share your delivery location');
    }

    public function test_seller_can_save_pickup_location(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->available()->create();
        $order = Order::factory()->for($listing)->paid()->create();

        $this->actingAs($seller)
            ->post(route('tracking.pickup-location', $order), [
                'address' => 'Westlands, Nairobi',
                'latitude' => -1.2649,
                'longitude' => 36.8029,
                'notes' => 'Gate B',
            ])
            ->assertRedirect()
            ->assertSessionHas('location_status');

        $listing->refresh();
        $this->assertSame('Westlands, Nairobi', $listing->pickup_address);
        $this->assertSame(-1.2649, $listing->pickup_latitude);
    }

    public function test_buyer_can_save_delivery_location(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create(['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->post(route('tracking.delivery-location', $order), [
                'address' => 'Kilimani, Nairobi',
                'latitude' => -1.2907,
                'longitude' => 36.7860,
            ])
            ->assertRedirect()
            ->assertSessionHas('location_status');

        $order->refresh();
        $this->assertSame('Kilimani, Nairobi', $order->delivery_address);
        $this->assertSame(-1.2907, $order->delivery_latitude);
    }

    public function test_buyer_cannot_save_pickup_location(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create(['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->post(route('tracking.pickup-location', $order), [
                'address' => 'Westlands',
                'latitude' => -1.26,
                'longitude' => 36.80,
            ])
            ->assertForbidden();
    }
}
