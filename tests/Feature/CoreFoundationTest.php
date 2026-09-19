<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\SellerPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CoreFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_the_user_role(): void
    {
        $user = User::create([
            'name' => 'Wanjiku',
            'email' => 'wanjiku@example.com',
            'password' => 'password',
        ]);

        $this->assertSame(User::ROLE_USER, $user->fresh()->role);
    }

    public function test_role_helpers_report_the_correct_role(): void
    {
        $user = User::factory()->create();
        $logistics = User::factory()->logistics()->create();
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());

        $this->assertTrue($logistics->isLogistics());
        $this->assertFalse($logistics->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSuperAdmin());

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertFalse($superAdmin->isLogistics());
    }

    public function test_user_owns_listings_orders_and_payouts(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $listing = Listing::factory()->for($seller)->available()->create();
        $order = Order::factory()->for($listing)->create(['buyer_id' => $buyer->id]);
        SellerPayout::factory()->for($order)->create(['seller_id' => $seller->id]);

        $this->assertTrue($seller->listings->contains($listing));
        $this->assertTrue($buyer->orders->contains($order));
        $this->assertCount(1, $seller->sellerPayouts);
    }

    public function test_order_and_payout_relationships_resolve(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create();
        $payout = SellerPayout::factory()->for($order)->create([
            'seller_id' => $order->listing->user_id,
            'paid_by' => $admin->id,
        ]);

        $this->assertTrue($order->listing->is($payout->seller->listings->first()));
        $this->assertTrue($order->buyer->exists);
        $this->assertTrue($order->sellerPayout->is($payout));
        $this->assertTrue($payout->order->is($order));
        $this->assertTrue($payout->paidBy->is($admin));
        $this->assertTrue($order->listing->latestOrder->is($order));
    }

    public function test_a_listing_keeps_its_historical_orders(): void
    {
        $listing = Listing::factory()->create();

        $cancelled = Order::factory()->for($listing)->create([
            'order_status' => Order::STATUS_CANCELLED,
            'created_at' => now()->subDay(),
        ]);
        $current = Order::factory()->for($listing)->paid()->create();

        $this->assertCount(2, $listing->orders);
        $this->assertTrue($listing->orders->contains($cancelled));
        $this->assertTrue($listing->latestOrder->is($current));
    }

    public function test_order_money_columns_use_sensible_defaults(): void
    {
        $listing = Listing::factory()->create();
        $buyer = User::factory()->create();

        $order = Order::create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'item_price' => 10500,
            'total_amount' => 10500,
        ])->fresh();

        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('0.00', $order->service_fee);
        $this->assertSame('10500.00', $order->item_price);
        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->order_status);
    }

    public function test_role_middleware_blocks_users_without_the_required_role(): void
    {
        Route::middleware(['web', 'auth', 'role:logistics'])
            ->get('/__test/logistics', fn () => 'ok');

        $this->actingAs(User::factory()->create())->get('/__test/logistics')->assertForbidden();
        $this->actingAs(User::factory()->logistics()->create())->get('/__test/logistics')->assertOk();
        $this->actingAs(User::factory()->superAdmin()->create())->get('/__test/logistics')->assertOk();
    }
}
