<?php

namespace App\Http\Controllers\Admin;

use App\Models\Listing;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PriceOverrideController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Listing $listing)
    {
        if ($request->user()->role !== 'super_admin') {
            abort_unless($request->wantsJson(), 403);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Draft and under-review sell items are exactly the ones that need a
        // manual price, so an override publishes them at the new price.
        $overridable = ['draft', 'under_review', 'available'];

        if (! in_array($listing->status, $overridable, true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Can only override prices for available listings.',
                ], 422);
            }

            return back()->withErrors(['new_price' => 'This listing can no longer be repriced.']);
        }

        $validated = $request->validate([
            'new_price' => 'required|numeric|min:1|max:9999999',
            'reason' => 'required|string|max:500',
        ]);

        // An unpriced draft has no old price; the audit column cannot be null.
        $oldPrice = $listing->final_price ?? $listing->suggested_price ?? 0;

        // Create audit entry
        $listing->priceOverrides()->create([
            'admin_id' => $request->user()->id,
            'old_price' => $oldPrice,
            'new_price' => $validated['new_price'],
            'reason' => $validated['reason'],
            'override_at' => now(),
        ]);

        // Update listing — an overridden price is a locked ReValue price, so
        // the item goes live for buyers.
        $listing->update([
            'final_price' => $validated['new_price'],
            'status' => 'available',
        ]);

        $message = "Price overridden from KSh {$oldPrice} to KSh {$validated['new_price']}";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'listing' => $listing,
            ]);
        }

        return back()->with('status', $message);
    }

    public function history(Request $request, Listing $listing)
    {
        if (! in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $overrides = $listing->priceOverrides()
            ->with('admin')
            ->orderBy('override_at', 'desc')
            ->get();

        return response()->json($overrides);
    }
}
