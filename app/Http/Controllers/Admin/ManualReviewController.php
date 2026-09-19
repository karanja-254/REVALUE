<?php

namespace App\Http\Controllers\Admin;

use App\Models\ManualReview;
use App\Models\PriceOverride;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ManualReviewController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ManualReview::class);

        $reviews = ManualReview::where('status', 'pending')
            ->with('listing', 'assignedAdmin')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($reviews);
    }

    public function approve(Request $request, ManualReview $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'final_price' => 'required|numeric|min:100|max:9999999',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldPrice = $review->listing->final_price;

        // Update listing
        $review->listing->update(['final_price' => $validated['final_price'], 'status' => 'available']);

        // Update review
        $review->update([
            'status' => 'reviewed',
            'notes' => $validated['notes'] ?? $review->notes,
            'assigned_admin_id' => $request->user()->id,
        ]);

        // Record in price_overrides for audit
        if ($oldPrice !== null && $oldPrice != $validated['final_price']) {
            $review->listing->priceOverrides()->create([
                'admin_id' => $request->user()->id,
                'old_price' => $oldPrice,
                'new_price' => $validated['final_price'],
                'reason' => 'Manual review: ' . ($validated['notes'] ?? ''),
                'override_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Item approved and priced at KSh ' . $validated['final_price'],
            'listing_id' => $review->listing->id,
        ]);
    }
}
