<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_start_checkout(): void
    {
        $listing = Listing::factory()->available()->create([
            'final_price' => 10500,
        ]);

        $this->post(route('checkout.store', $listing))->assertRedirect(route('login'));
    }

    public function test_seller_cannot_buy_their_own_listing(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->available()->create([
            'final_price' => 10500,
        ]);

        $this->actingAs($seller)
            ->post(route('checkout.store', $listing))
            ->assertForbidden();
    }

    public function test_buyer_is_sent_to_paystack_after_initialize(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/revalue-test',
                    'access_code' => 'access',
                    'reference' => 'RV-TESTREF',
                ],
            ], 200),
        ]);

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create([
            'final_price' => 10500,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing))
            ->assertRedirect('https://checkout.paystack.com/revalue-test');

        $this->assertDatabaseHas('orders', [
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_status' => Order::PAYMENT_PENDING,
        ]);
    }

    public function test_callback_does_not_mark_paid_without_paystack_success(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'failed',
                    'reference' => 'RV-FAIL1',
                    'amount' => 1140000,
                    'currency' => 'KES',
                ],
            ], 200),
        ]);

        $order = Order::factory()->create([
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-FAIL1',
        ]);

        $this->actingAs($order->buyer)
            ->get(route('paystack.callback', ['reference' => 'RV-FAIL1']))
            ->assertRedirect(route('orders.failed'));

        $this->assertSame(Order::PAYMENT_FAILED, $order->fresh()->payment_status);
        $this->assertNotSame(Listing::STATUS_SOLD, $order->listing->fresh()->status);
    }

    public function test_verified_callback_marks_order_paid_and_listing_sold(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'RV-OK1',
                    'amount' => 1140000,
                    'currency' => 'KES',
                    'channel' => 'card',
                ],
            ], 200),
        ]);

        $listing = Listing::factory()->available()->create(['final_price' => 10500]);
        $buyer = User::factory()->create();
        $order = Order::factory()->for($listing)->create([
            'buyer_id' => $buyer->id,
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-OK1',
        ]);

        $this->actingAs($buyer)
            ->get(route('paystack.callback', ['reference' => 'RV-OK1']))
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_PAID, $order->order_status);
        $this->assertNotNull($order->pickup_pin);
        $this->assertNotNull($order->delivery_pin);
        $this->assertSame(Listing::STATUS_SOLD, $listing->fresh()->status);
        $this->assertDatabaseHas('seller_payouts', [
            'order_id' => $order->id,
            'seller_id' => $listing->user_id,
            'status' => SellerPayout::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('payments', [
            'reference' => 'RV-OK1',
            'status' => Payment::STATUS_PAID,
        ]);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'RV-LOW',
                    'amount' => 500,
                    'currency' => 'KES',
                ],
            ], 200),
        ]);

        $order = Order::factory()->create([
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-LOW',
        ]);

        $this->actingAs($order->buyer)
            ->get(route('paystack.callback', ['reference' => 'RV-LOW']))
            ->assertRedirect(route('orders.failed'));

        $this->assertSame(Order::PAYMENT_FAILED, $order->fresh()->payment_status);
    }

    public function test_webhook_with_valid_signature_marks_paid(): void
    {
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => 'RV-HOOK',
                'amount' => 1140000,
                'currency' => 'KES',
                'channel' => 'mobile_money',
            ],
        ];
        $raw = json_encode($payload);
        $signature = hash_hmac('sha512', $raw, 'sk_test_revalue_dummy');

        $listing = Listing::factory()->available()->create(['final_price' => 10500]);
        $order = Order::factory()->for($listing)->create([
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-HOOK',
        ]);

        $this->call(
            'POST',
            route('paystack.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $raw
        )->assertOk();

        $this->assertSame(Order::PAYMENT_PAID, $order->fresh()->payment_status);
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        $this->postJson(route('paystack.webhook'), [
            'event' => 'charge.success',
            'data' => ['reference' => 'RV-BAD'],
        ], [
            'X-Paystack-Signature' => 'not-valid',
        ])->assertUnauthorized();
    }
}
