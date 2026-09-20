<?php

namespace App\Http\Controllers\Admin;

use App\Models\ManualReview;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ManualReviewController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ManualReview::class);

        $reviews = ManualReview::where('status', 'pending')
            ->with('listing.user', 'assignedAdmin')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($reviews);
        }

        return view('admin.manual-reviews.index', [
            'reviews' => $reviews,
        ]);
    }

    public function approve(Request $request, ManualReview $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'final_price' => 'required|numeric|min:1|max:9999999',
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
                'reason' => 'Manual review: '.($validated['notes'] ?? ''),
                'override_at' => now(),
            ]);
        }

        $message = 'Item approved and priced at KSh '.$validated['final_price'];

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'listing_id' => $review->listing->id,
            ]);
        }

        return back()->with('status', $message);
    }
}
