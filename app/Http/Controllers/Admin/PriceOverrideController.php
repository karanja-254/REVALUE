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
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($listing->status !== 'available') {
            return response()->json([
                'error' => 'Can only override prices for available listings.',
            ], 422);
        }

        $validated = $request->validate([
            'new_price' => 'required|numeric|min:100|max:9999999',
            'reason' => 'required|string|max:500',
        ]);

        $oldPrice = $listing->final_price;

        // Create audit entry
        $listing->priceOverrides()->create([
            'admin_id' => $request->user()->id,
            'old_price' => $oldPrice,
            'new_price' => $validated['new_price'],
            'reason' => $validated['reason'],
            'override_at' => now(),
        ]);

        // Update listing
        $listing->update(['final_price' => $validated['new_price']]);

        return response()->json([
            'message' => "Price overridden from KSh {$oldPrice} to KSh {$validated['new_price']}",
            'listing' => $listing,
        ]);
    }

    public function history(Request $request, Listing $listing)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $overrides = $listing->priceOverrides()
            ->with('admin')
            ->orderBy('override_at', 'desc')
            ->get();

        return response()->json($overrides);
    }
}
