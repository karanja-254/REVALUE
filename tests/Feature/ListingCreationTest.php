<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_listing(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('item.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/listings', [
            'type' => 'sell',
            'title' => 'Samsung TV 43"',
            'description' => 'Good condition',
            'image' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'listing_id']);

        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'type' => 'sell',
            'title' => 'Samsung TV 43"',
            'status' => 'draft',
            'processing_status' => 'pending',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_listing(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('item.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/listings', [
            'type' => 'sell',
            'title' => 'Test Item',
            'image' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_view_own_listing(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/listings/{$listing->id}");

        $response->assertStatus(200);
        $response->assertJson(['id' => $listing->id]);
    }
}
