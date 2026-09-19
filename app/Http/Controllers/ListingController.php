<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessListingWithAI;
use App\Models\Listing;
use App\Models\ManualReview;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:sell,donate,recycle',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|max:5120',
        ]);

        // For MVP, save image locally. In production, use Cloudinary/S3
        $imagePath = $request->file('image')->store('listings', 'public');

        // Create listing in draft
        $listing = Listing::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'status' => 'draft',
            'processing_status' => 'pending',
        ]);

        // Dispatch AI processing job
        ProcessListingWithAI::dispatch($listing, $imagePath);

        return response()->json([
            'message' => 'Listing created. AI is analyzing your item...',
            'listing_id' => $listing->id,
        ], 201);
    }

    public function show(Listing $listing)
    {
        $this->authorize('view', $listing);

        return response()->json($listing->load('manualReview', 'priceOverrides'));
    }

    public function acceptPrice(Listing $listing)
    {
        $this->authorize('view', $listing);

        if ($listing->suggested_price === null) {
            return response()->json([
                'error' => 'No suggested price available. Item requires manual review.',
            ], 422);
        }

        $listing->update([
            'final_price' => $listing->suggested_price,
            'status' => 'available',
        ]);

        return response()->json([
            'message' => 'Price accepted. Item is now available for purchase.',
            'listing' => $listing,
        ]);
    }

    public function requestReview(Request $request, Listing $listing)
    {
        $this->authorize('view', $listing);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($listing->manualReview) {
            return response()->json([
                'error' => 'Item is already under manual review.',
            ], 422);
        }

        ManualReview::create([
            'listing_id' => $listing->id,
            'status' => 'pending',
            'notes' => 'Seller request: ' . ($validated['reason'] ?? 'No reason provided'),
        ]);

        $listing->update(['status' => 'under_review']);

        return response()->json([
            'message' => 'Your item has been submitted for manual pricing review. An admin will review it shortly.',
        ]);
    }
}
