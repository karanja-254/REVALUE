<?php

namespace Tests\Feature;

use App\Jobs\ProcessListingWithAI;
use App\Models\Listing;
use App\Models\ManualReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_users_cannot_sell_donate_recycle_or_claim(): void
    {
        $driver = User::factory()->logistics()->create();
        $donation = Listing::factory()->donation()->available()->create();

        $this->actingAs($driver)->get(route('listings.create'))->assertForbidden();
        $this->actingAs($driver)->post(route('listings.store'), [
            'type' => 'sell',
            'title' => 'Should not work',
            'category' => 'electronics',
            'condition' => 'good',
        ])->assertForbidden();
        $this->actingAs($driver)->post(route('donations.claim', $donation))->assertForbidden();
    }

    public function test_uploaded_listing_image_resolves_to_a_real_url(): void
    {
        Storage::fake('public');
        Queue::fake();

        $seller = User::factory()->create();

        $this->actingAs($seller)->post(route('listings.store'), [
            'type' => 'sell',
            'title' => 'MrBeanface armchair',
            'description' => 'Demo upload',
            'category' => 'furniture',
            'condition' => 'good',
            'image' => UploadedFile::fake()->image('mrbeanface.jpg'),
        ])->assertRedirect();

        $listing = Listing::where('title', 'MrBeanface armchair')->firstOrFail();

        $this->assertNotNull($listing->image_path);
        Storage::disk('public')->assertExists($listing->image_path);
        $this->assertStringContainsString($listing->image_path, $listing->imageUrl());
        $this->assertSame($listing->imageUrl(), $listing->coverImage());

        Queue::assertPushed(ProcessListingWithAI::class);
    }

    public function test_super_admin_override_publishes_the_listing_at_the_new_price(): void
    {
        $churchill = User::factory()->superAdmin()->create();
        $listing = Listing::factory()->create([
            'status' => Listing::STATUS_UNDER_REVIEW,
            'suggested_price' => null,
            'final_price' => null,
        ]);

        $this->actingAs($churchill)
            ->get(route('listings.show', $listing))
            ->assertSee('Override price');

        $this->actingAs($churchill)
            ->post(route('admin.listings.override-price', $listing), [
                'new_price' => 5,
                'reason' => 'Hackathon demo price',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertEquals(5, $listing->final_price);
        $this->assertSame(Listing::STATUS_AVAILABLE, $listing->status);

        $this->get(route('listings.show', $listing))->assertSee('KSh 5');
    }

    public function test_seller_can_accept_the_suggested_price_from_the_listing_page(): void
    {
        $karanja = User::factory()->create();
        $ronald = User::factory()->create();

        // State after AI pricing: still a draft, but with a suggested price.
        $listing = Listing::factory()->for($karanja)->create([
            'title' => 'Karanja demo armchair',
            'status' => Listing::STATUS_DRAFT,
            'suggested_price' => 8,
            'final_price' => null,
        ]);

        $this->actingAs($karanja)
            ->get(route('listings.show', $listing))
            ->assertSee('Accept KSh 8 and publish')
            ->assertSee('Request manual review');

        $this->actingAs($karanja)
            ->put(route('listings.accept-price', $listing))
            ->assertRedirect(route('listings.show', $listing))
            ->assertSessionHas('status');

        $listing->refresh();

        $this->assertSame(Listing::STATUS_AVAILABLE, $listing->status);
        $this->assertEquals(8, $listing->final_price);

        $this->actingAs($ronald)
            ->get(route('listings.index'))
            ->assertSee($listing->title);
    }

    public function test_super_admin_override_makes_a_draft_visible_to_buyers(): void
    {
        $churchill = User::factory()->superAdmin()->create();
        $ronald = User::factory()->create();
        $listing = Listing::factory()->create([
            'title' => 'Karanja MrBeanface chair',
            'status' => Listing::STATUS_DRAFT,
            'suggested_price' => null,
            'final_price' => null,
        ]);

        $this->actingAs($churchill)->post(route('admin.listings.override-price', $listing), [
            'new_price' => 5,
            'reason' => 'Hackathon demo price',
        ])->assertRedirect();

        $this->actingAs($ronald)
            ->get(route('listings.index'))
            ->assertSee($listing->title)
            ->assertSee('KSh 5');
    }

    public function test_manual_review_lets_an_admin_publish_at_ksh_5(): void
    {
        $karanja = User::factory()->create();
        $churchill = User::factory()->superAdmin()->create();
        $ronald = User::factory()->create();

        $listing = Listing::factory()->for($karanja)->create([
            'title' => 'Karanja review item',
            'status' => Listing::STATUS_DRAFT,
            'suggested_price' => null,
            'final_price' => null,
        ]);

        $this->actingAs($karanja)
            ->put(route('listings.request-review', $listing), ['reason' => 'No offer yet'])
            ->assertRedirect(route('listings.show', $listing));

        $this->assertSame(Listing::STATUS_UNDER_REVIEW, $listing->fresh()->status);

        $review = ManualReview::where('listing_id', $listing->id)->firstOrFail();

        $this->actingAs($churchill)
            ->get(route('dashboard'))
            ->assertSee('Manual reviews');

        $this->actingAs($churchill)
            ->get(route('admin.manual-reviews.index'))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee($karanja->name)
            ->assertSee('Approve &amp; Publish', false);

        $this->actingAs($churchill)
            ->put(route('admin.manual-reviews.approve', $review), [
                'final_price' => 5,
                'notes' => 'Demo price',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertEquals(5, $listing->final_price);
        $this->assertSame(Listing::STATUS_AVAILABLE, $listing->status);
        $this->assertSame('reviewed', $review->fresh()->status);

        $this->actingAs($ronald)->get(route('listings.index'))->assertSee($listing->title);
    }

    public function test_only_admins_can_reach_manual_reviews(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.manual-reviews.index'))->assertOk();
        $this->actingAs(User::factory()->logistics()->create())
            ->get(route('admin.manual-reviews.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())
            ->get(route('admin.manual-reviews.index'))->assertForbidden();
    }

    public function test_charity_application_cta_is_only_offered_to_normal_users(): void
    {
        $this->get(route('organizations.index'))->assertSee('Log in to apply');

        $this->actingAs(User::factory()->create())
            ->get(route('organizations.index'))
            ->assertSee('Apply for verification');

        foreach ([
            User::factory()->admin()->create(),
            User::factory()->superAdmin()->create(),
            User::factory()->logistics()->create(),
        ] as $staff) {
            $this->actingAs($staff)
                ->get(route('organizations.index'))
                ->assertDontSee('Apply for verification')
                ->assertDontSee('Log in to apply');
        }
    }

    public function test_marketplace_shows_newest_available_sell_listings_first(): void
    {
        $older = Listing::factory()->available()->create([
            'title' => 'Older demo item',
            'created_at' => now()->subDay(),
        ]);
        $newest = Listing::factory()->available()->create([
            'title' => 'Newest demo item',
            'created_at' => now(),
        ]);
        $draft = Listing::factory()->create(['title' => 'Hidden draft item']);
        $underReview = Listing::factory()->create([
            'title' => 'Hidden review item',
            'status' => Listing::STATUS_UNDER_REVIEW,
        ]);

        $this->get(route('listings.index'))
            ->assertSeeInOrder([$newest->title, $older->title])
            ->assertDontSee($draft->title)
            ->assertDontSee($underReview->title);

        $this->get(route('home'))->assertSeeInOrder([$newest->title, $older->title]);
    }
}
