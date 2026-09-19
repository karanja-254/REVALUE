<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiCharityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_revalue_paths(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Give Things Another Life');
        $response->assertSee('Sell something');
        $response->assertSee('Donate something');
        $response->assertSee('Recycle something');
        $response->assertSee('No bargaining');
    }

    public function test_marketplace_lists_available_sell_items(): void
    {
        Listing::factory()->available()->create(['title' => 'Samsung 43" Smart TV']);
        Listing::factory()->donation()->available()->create(['title' => 'Hidden mattress']);

        $response = $this->get(route('listings.index'));

        $response->assertOk();
        $response->assertSee('Samsung 43" Smart TV');
        $response->assertDontSee('Hidden mattress');
    }

    public function test_guest_is_sent_to_login_before_creating_a_listing(): void
    {
        $this->get(route('listings.create'))->assertRedirect(route('login'));
    }

    public function test_verified_user_can_create_sell_donate_and_recycle_listings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.store'), [
            'type' => Listing::TYPE_SELL,
            'title' => 'Office desk',
            'description' => 'Solid wood',
            'category' => 'office',
            'condition' => 'good',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('listings.store'), [
            'type' => Listing::TYPE_DONATE,
            'title' => 'Spare mattress',
            'category' => 'mattresses',
            'condition' => 'good',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('listings.store'), [
            'type' => Listing::TYPE_RECYCLE,
            'title' => 'Dead television',
            'category' => 'electronics',
            'condition' => 'damaged',
        ])->assertRedirect();

        $this->assertDatabaseHas('listings', [
            'title' => 'Office desk',
            'type' => Listing::TYPE_SELL,
            'status' => Listing::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('listings', [
            'title' => 'Spare mattress',
            'type' => Listing::TYPE_DONATE,
            'status' => Listing::STATUS_AVAILABLE,
        ]);
        $this->assertDatabaseHas('listings', [
            'title' => 'Dead television',
            'status' => Listing::STATUS_AVAILABLE,
        ]);
    }

    public function test_user_can_apply_for_charity_verification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('organizations.store'), [
            'type' => Organization::TYPE_CHARITY,
            'name' => "Hope Children's Home",
            'contact_person' => 'Mercy',
            'email' => 'hope@example.com',
            'phone' => '0712345678',
            'location' => 'Parklands, Nairobi',
            'registration_details' => 'NGO-2041',
        ])->assertRedirect(route('organizations.create'));

        $this->assertDatabaseHas('organizations', [
            'user_id' => $user->id,
            'name' => "Hope Children's Home",
            'verification_status' => Organization::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_verify_a_charity(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.verify', $organization))
            ->assertRedirect();

        $this->assertTrue($organization->fresh()->isVerified());
    }

    public function test_unverified_charity_cannot_claim_a_donation(): void
    {
        $user = User::factory()->create();
        Organization::factory()->for($user)->create();
        $listing = Listing::factory()->donation()->available()->create();

        $this->actingAs($user)
            ->post(route('donations.claim', $listing))
            ->assertForbidden();
    }

    public function test_verified_charity_can_claim_an_available_donation(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->for($user)->verified()->create();
        $listing = Listing::factory()->donation()->available()->create(['title' => 'Double mattress']);

        $this->actingAs($user)
            ->post(route('donations.claim', $listing))
            ->assertRedirect(route('listings.show', $listing));

        $this->assertSame(Listing::STATUS_DONATED, $listing->fresh()->status);
        $this->assertDatabaseHas('donation_claims', [
            'listing_id' => $listing->id,
            'organization_id' => $organization->id,
            'claimed_by_user_id' => $user->id,
        ]);
    }

    public function test_unverified_charity_does_not_appear_on_the_public_list(): void
    {
        Organization::factory()->create(['name' => 'Fake Home']);
        Organization::factory()->verified()->create(['name' => 'Hope Home']);

        $response = $this->get(route('organizations.index'));

        $response->assertOk();
        $response->assertSee('Hope Home');
        $response->assertDontSee('Fake Home');
    }

    public function test_buyer_can_rate_a_completed_order(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->completed()->create(['buyer_id' => $buyer->id]);

        $this->actingAs($buyer)->post(route('reviews.store', $order), [
            'accurate_description' => 5,
            'smooth_delivery' => 4,
            'professional_handling' => 5,
            'punctual_pickup' => 4,
            'comment' => 'Item matched the listing.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'reviewer_id' => $buyer->id,
        ]);
        $this->assertSame(4.5, Review::first()->averageScore());
    }

    public function test_buyer_cannot_rate_the_same_order_twice(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->completed()->create(['buyer_id' => $buyer->id]);
        Review::factory()->for($order)->create(['reviewer_id' => $buyer->id]);

        $this->actingAs($buyer)->post(route('reviews.store', $order), [
            'accurate_description' => 3,
            'smooth_delivery' => 3,
            'professional_handling' => 3,
            'punctual_pickup' => 3,
        ])->assertForbidden();
    }
}
