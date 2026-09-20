<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\SellerPayout;
use App\Models\User;
use App\Support\KenyanPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function fakeStkPush(string $reference = 'RV-STK'): void
    {
        Http::fake([
            'https://api.paystack.co/charge' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'pay_offline',
                    'reference' => $reference,
                    'display_text' => 'Please complete the authorization process on your phone',
                ],
            ], 200),
        ]);
    }

    public function test_buyer_sees_a_phone_input_and_no_email_input(): void
    {
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('listings.show', $listing));

        $response->assertSee('M-PESA phone number')
            ->assertSee('name="phone"', false)
            ->assertSee('0712345678')
            ->assertDontSee('name="email"', false);
    }

    public function test_kenyan_numbers_normalize_to_the_international_format(): void
    {
        foreach (['0712345678', '712345678', '254712345678', '+254712345678', '0712 345 678'] as $input) {
            $this->assertSame('+254712345678', KenyanPhone::normalize($input), $input);
        }

        $this->assertSame('+254112345678', KenyanPhone::normalize('0112345678'));

        foreach (['', '07123', '0812345678', 'not a phone', '+1234567890'] as $invalid) {
            $this->assertNull(KenyanPhone::normalize($invalid), $invalid);
        }
    }

    public function test_charge_uses_account_email_mpesa_provider_and_normalized_phone(): void
    {
        $this->fakeStkPush();

        $buyer = User::factory()->create(['email' => 'ronald@example.com']);
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect();

        Http::assertSent(function ($request) use ($buyer) {
            return $request->url() === 'https://api.paystack.co/charge'
                && $request['email'] === $buyer->email
                && $request['currency'] === 'KES'
                && $request['mobile_money']['provider'] === 'mpesa'
                && $request['mobile_money']['phone'] === '+254712345678';
        });
    }

    public function test_reserved_demo_domains_fall_back_to_the_billing_email(): void
    {
        config(['revalue.paystack.billing_email' => 'payments@revalue.co.ke']);
        $this->fakeStkPush();

        $buyer = User::factory()->create(['email' => 'ronald@revalue.test']);
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request['email'] === 'payments@revalue.co.ke');
    }

    public function test_ksh_5_demo_charge_sends_500_subunits(): void
    {
        config(['revalue.fees.delivery' => 0, 'revalue.fees.service' => 0]);
        $this->fakeStkPush();

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $listing), ['phone' => '0712345678'])
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request['amount'] === 500);
    }

    public function test_an_invalid_phone_number_never_reaches_paystack(): void
    {
        Http::fake();

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)
            ->from(route('listings.show', $listing))
            ->post(route('checkout.store', $listing), ['phone' => '12345'])
            ->assertSessionHasErrors('phone');

        Http::assertNothingSent();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_stk_initiation_alone_does_not_mark_the_order_paid(): void
    {
        $this->fakeStkPush();

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)->post(route('checkout.store', $listing), ['phone' => '0712345678']);

        $order = Order::where('buyer_id', $buyer->id)->firstOrFail();

        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->order_status);
        $this->assertSame(Listing::STATUS_AVAILABLE, $listing->fresh()->status);
        $this->assertDatabaseCount('seller_payouts', 0);
    }

    public function test_polling_keeps_waiting_while_paystack_reports_pending(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'pending', 'reference' => 'RV-WAIT', 'amount' => 500, 'currency' => 'KES'],
            ], 200),
        ]);

        $order = Order::factory()->create([
            'item_price' => 5,
            'delivery_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 5,
            'payment_reference' => 'RV-WAIT',
        ]);

        $this->actingAs($order->buyer)
            ->getJson(route('checkout.status', $order))
            ->assertOk()
            ->assertJson(['paid' => false, 'payment_status' => Order::PAYMENT_PENDING]);

        $this->assertSame(Order::PAYMENT_PENDING, $order->fresh()->payment_status);
    }

    public function test_verified_mpesa_success_pays_the_order_and_opens_a_pending_payout(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'RV-OKSTK',
                    'amount' => 500,
                    'currency' => 'KES',
                    'channel' => 'mobile_money',
                ],
            ], 200),
        ]);

        $listing = Listing::factory()->available()->create(['final_price' => 5]);
        $order = Order::factory()->for($listing)->create([
            'item_price' => 5,
            'delivery_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 5,
            'payment_reference' => 'RV-OKSTK',
        ]);

        $this->actingAs($order->buyer)
            ->getJson(route('checkout.status', $order))
            ->assertOk()
            ->assertJson(['paid' => true]);

        $order->refresh();

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_PAID, $order->order_status);
        $this->assertSame(Listing::STATUS_SOLD, $listing->fresh()->status);
        $this->assertNotNull($order->pickup_pin);
        $this->assertNotNull($order->delivery_pin);
        $this->assertDatabaseHas('seller_payouts', [
            'order_id' => $order->id,
            'seller_id' => $listing->user_id,
            'status' => SellerPayout::STATUS_PENDING,
        ]);
    }

    public function test_failed_mpesa_charge_does_not_pay_the_order(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'reference' => 'RV-NOSTK', 'amount' => 500, 'currency' => 'KES'],
            ], 200),
        ]);

        $listing = Listing::factory()->available()->create(['final_price' => 5]);
        $order = Order::factory()->for($listing)->create([
            'item_price' => 5,
            'delivery_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 5,
            'payment_reference' => 'RV-NOSTK',
        ]);

        $this->actingAs($order->buyer)
            ->getJson(route('checkout.status', $order))
            ->assertOk()
            ->assertJson(['paid' => false, 'payment_status' => Order::PAYMENT_FAILED]);

        $this->assertSame(Listing::STATUS_AVAILABLE, $listing->fresh()->status);
        $this->assertDatabaseCount('seller_payouts', 0);
    }

    public function test_retrying_the_stk_push_reuses_the_same_order(): void
    {
        $this->fakeStkPush();

        $buyer = User::factory()->create();
        $listing = Listing::factory()->available()->create(['final_price' => 5]);

        $this->actingAs($buyer)->post(route('checkout.store', $listing), ['phone' => '0712345678']);
        $this->actingAs($buyer)->post(route('checkout.store', $listing), ['phone' => '0712345678']);

        $this->assertSame(1, Order::where('listing_id', $listing->id)->count());
    }

    public function test_waiting_page_tells_the_buyer_to_use_the_phone_prompt(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->buyer)
            ->get(route('checkout.waiting', $order))
            ->assertOk()
            ->assertSee('Check your phone')
            ->assertSee('Waiting for M-PESA confirmation');

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.waiting', $order))
            ->assertForbidden();
    }
}
