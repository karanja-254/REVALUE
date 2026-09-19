<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Listing;
use App\Support\ListingOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();

        if (! in_array($type, Listing::TYPES, true)) {
            $type = Listing::TYPE_SELL;
        }

        $listings = Listing::query()
            ->with('user')
            ->where('type', $type)
            ->where('status', Listing::STATUS_AVAILABLE)
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('listings.index', [
            'listings' => $listings,
            'type' => $type,
        ]);
    }

    public function mine(Request $request): View
    {
        return view('listings.mine', [
            'listings' => $request->user()->listings()->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->string('type')->toString();

        if (! in_array($type, Listing::TYPES, true)) {
            $type = Listing::TYPE_SELL;
        }

        return view('listings.create', [
            'type' => $type,
            'categories' => ListingOptions::categoryLabels(),
            'conditions' => ListingOptions::conditionLabels(),
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'];

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('listings', 'public');
        }

        $listing = Listing::create([
            'user_id' => $request->user()->id,
            'type' => $type,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'condition' => $data['condition'],
            'image_path' => $imagePath,
            'suggested_price' => null,
            'final_price' => null,
            'status' => $type === Listing::TYPE_SELL
                ? Listing::STATUS_DRAFT
                : Listing::STATUS_AVAILABLE,
        ]);

        $message = match ($type) {
            Listing::TYPE_SELL => 'Listing saved. ReValue pricing will lock a fixed offer next — no bargaining after you accept.',
            Listing::TYPE_DONATE => 'Donation listed. Verified charities can now claim it. You will not pay for collection.',
            default => 'Item listed for recycling. Verified recyclers can arrange collection.',
        };

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', $message);
    }

    public function show(Listing $listing): View
    {
        $listing->load(['user', 'donationClaim.organization']);

        return view('listings.show', [
            'listing' => $listing,
        ]);
    }
}
