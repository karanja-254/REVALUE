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

    public function test_buyer_is_sent_to_the_waiting_page_after_stk_initiation(): void
    {
        Http::fake([
            'https://api.paystack.co/charge' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'pay_offline',
                    'reference' => 'RV-TESTREF',
                    'display_text' => 'Please complete the authorization process on your phone',
                ],
            ], 200),
        ]);

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create([
            'final_price' => 10500,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect(route('checkout.waiting', Order::where('buyer_id', $buyer->id)->firstOrFail()));

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

    public function test_second_buyer_cannot_start_checkout_while_listing_is_reserved(): void
    {
        Http::fake([
            'https://api.paystack.co/charge' => Http::response([
                'status' => true,
                'data' => ['status' => 'pay_offline', 'reference' => 'RV-RESERVE'],
            ], 200),
        ]);

        $listing = Listing::factory()->available()->create(['final_price' => 10500]);
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();

        $this->actingAs($buyerA)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect(route('checkout.waiting', Order::where('buyer_id', $buyerA->id)->firstOrFail()));

        $this->actingAs($buyerB)
            ->from(route('listings.show', $listing))
            ->post(route('checkout.store', $listing), ['phone' => '0722333444'])
            ->assertRedirect(route('listings.show', $listing))
            ->assertSessionHasErrors('checkout');

        $this->assertSame(1, Order::query()->where('listing_id', $listing->id)->count());
        $this->assertSame($buyerA->id, Order::query()->where('listing_id', $listing->id)->value('buyer_id'));
    }

    public function test_duplicate_successful_charge_is_flagged_for_refund_not_failed(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/RV-WIN' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'RV-WIN',
                    'amount' => 1140000,
                    'currency' => 'KES',
                ],
            ], 200),
            'https://api.paystack.co/transaction/verify/RV-LOSE' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'RV-LOSE',
                    'amount' => 1140000,
                    'currency' => 'KES',
                ],
            ], 200),
        ]);

        $listing = Listing::factory()->available()->create(['final_price' => 10500]);
        $winner = User::factory()->create();
        $loser = User::factory()->create();

        $winningOrder = Order::factory()->for($listing)->create([
            'buyer_id' => $winner->id,
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-WIN',
        ]);
        $losingOrder = Order::factory()->for($listing)->create([
            'buyer_id' => $loser->id,
            'item_price' => 10500,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 11400,
            'payment_reference' => 'RV-LOSE',
        ]);

        $this->actingAs($winner)
            ->get(route('paystack.callback', ['reference' => 'RV-WIN']))
            ->assertRedirect(route('orders.show', $winningOrder));

        $this->actingAs($loser)
            ->get(route('paystack.callback', ['reference' => 'RV-LOSE']))
            ->assertRedirect(route('orders.show', $losingOrder));

        $this->assertSame(Order::PAYMENT_PAID, $winningOrder->fresh()->payment_status);
        $this->assertSame(Listing::STATUS_SOLD, $listing->fresh()->status);
        $this->assertSame(Order::PAYMENT_REFUND_REQUIRED, $losingOrder->fresh()->payment_status);
        $this->assertNotSame(Order::PAYMENT_FAILED, $losingOrder->fresh()->payment_status);
        $this->assertDatabaseHas('payments', [
            'reference' => 'RV-LOSE',
            'status' => Payment::STATUS_REFUND_REQUIRED,
        ]);
        $this->assertDatabaseMissing('seller_payouts', [
            'order_id' => $losingOrder->id,
        ]);
    }

    public function test_ksh_5_override_checkout_uses_price_plus_fees_in_subunits(): void
    {
        Http::fake([
            'https://api.paystack.co/charge' => Http::response([
                'status' => true,
                'data' => ['status' => 'pay_offline', 'reference' => 'RV-KSH5'],
            ], 200),
        ]);

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create([
            'suggested_price' => 8000,
            'final_price' => 5,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect(route('checkout.waiting', Order::where('buyer_id', $buyer->id)->firstOrFail()));

        $this->assertDatabaseHas('orders', [
            'listing_id' => $listing->id,
            'item_price' => 5,
            'delivery_fee' => 600,
            'service_fee' => 300,
            'total_amount' => 905,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.paystack.co/charge'
                && $request['amount'] === 90500
                && $request['currency'] === 'KES';
        });
    }

    public function test_demo_zero_fees_charge_only_the_item_price(): void
    {
        config(['revalue.fees.delivery' => 0, 'revalue.fees.service' => 0]);

        Http::fake([
            'https://api.paystack.co/charge' => Http::response([
                'status' => true,
                'data' => ['status' => 'pay_offline', 'reference' => 'RV-DEMO5'],
            ], 200),
        ]);

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect(route('checkout.waiting', Order::where('buyer_id', $buyer->id)->firstOrFail()));

        $this->assertDatabaseHas('orders', [
            'listing_id' => $listing->id,
            'item_price' => 5,
            'delivery_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 5,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.paystack.co/charge'
                && $request['amount'] === 500
                && $request['currency'] === 'KES';
        });
    }
}
