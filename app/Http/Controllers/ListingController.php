<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessListingWithAI;
use App\Models\Listing;
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
}
