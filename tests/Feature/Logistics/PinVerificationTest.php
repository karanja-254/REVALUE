<?php

namespace Tests\Feature\Logistics;

use App\Exceptions\PinVerificationException;
use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use App\Models\SellerPayout;
use App\Services\Logistics\PinVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function pickupStop(string $orderStatus = Order::STATUS_SCHEDULED): RouteStop
    {
        $order = Order::factory()->paid()->create(['order_status' => $orderStatus]);
        $route = LogisticsRoute::factory()->create();

        return RouteStop::factory()->pickup()->create([
            'route_id' => $route->id,
            'order_id' => $order->id,
        ]);
    }

    private function deliveryStop(): RouteStop
    {
        $order = Order::factory()->paid()->create(['order_status' => Order::STATUS_PICKED_UP]);
        $route = LogisticsRoute::factory()->create();

        return RouteStop::factory()->delivery()->create([
            'route_id' => $route->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_successful_pickup_marks_order_picked_up_and_payout_ready(): void
    {
        $stop = $this->pickupStop();
        $order = $stop->order;
        SellerPayout::factory()->for($order)->create([
            'seller_id' => $order->listing->user_id,
            'status' => SellerPayout::STATUS_PENDING,
        ]);

        app(PinVerificationService::class)->verifyPickup($stop, true, $order->pickup_pin);

        $this->assertSame(Order::STATUS_PICKED_UP, $order->fresh()->order_status);
        $this->assertNotNull($order->fresh()->pickup_verified_at);
        $this->assertSame(RouteStop::STATUS_COMPLETED, $stop->fresh()->status);
        $this->assertSame(RouteStop::RESULT_MATCHED, $stop->fresh()->verification_result);
        $this->assertSame(SellerPayout::STATUS_READY, $order->sellerPayout->fresh()->status);
    }

    public function test_wrong_pickup_pin_is_rejected(): void
    {
        $stop = $this->pickupStop();

        $this->expectException(PinVerificationException::class);

        app(PinVerificationService::class)->verifyPickup($stop, true, '0000-wrong');
    }

    public function test_item_mismatch_fails_the_pickup_without_a_pin(): void
    {
        $stop = $this->pickupStop();

        app(PinVerificationService::class)->verifyPickup($stop, false, null, 'Screen cracked, listing said working.');

        $this->assertSame(Order::STATUS_PICKUP_FAILED, $stop->order->fresh()->order_status);
        $this->assertSame(RouteStop::STATUS_FAILED, $stop->fresh()->status);
        $this->assertSame(RouteStop::RESULT_MISMATCH, $stop->fresh()->verification_result);
    }

    public function test_successful_delivery_completes_the_order(): void
    {
        $stop = $this->deliveryStop();
        $order = $stop->order;

        app(PinVerificationService::class)->verifyDelivery($stop, $order->delivery_pin);

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->order_status);
        $this->assertNotNull($order->fresh()->delivery_verified_at);
        $this->assertSame(RouteStop::STATUS_COMPLETED, $stop->fresh()->status);
    }

    public function test_wrong_delivery_pin_is_rejected(): void
    {
        $stop = $this->deliveryStop();

        $this->expectException(PinVerificationException::class);

        app(PinVerificationService::class)->verifyDelivery($stop, '9999999');
    }

    public function test_marking_a_delivery_en_route_sets_the_order_out_for_delivery(): void
    {
        $stop = $this->deliveryStop();

        app(PinVerificationService::class)->updateStopStatus($stop, RouteStop::STATUS_EN_ROUTE);

        $this->assertSame(Order::STATUS_OUT_FOR_DELIVERY, $stop->order->fresh()->order_status);
    }
}
