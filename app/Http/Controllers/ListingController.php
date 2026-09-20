<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Jobs\ProcessListingWithAI;
use App\Models\Listing;
use App\Models\ManualReview;
use App\Support\ListingOptions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ListingController extends Controller
{
    use AuthorizesRequests;

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

    public function store(StoreListingRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $type = $data['type'];
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('listings', 'public');
        }

        if ($request->wantsJson()) {
            $listing = Listing::create([
                'user_id' => $request->user()->id,
                'type' => $type,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'image_path' => $imagePath,
                'status' => Listing::STATUS_DRAFT,
                'processing_status' => 'pending',
            ]);

            $this->dispatchAiProcessing($request, $listing, $imagePath);

            return response()->json([
                'message' => 'Listing created. AI is analyzing your item...',
                'listing_id' => $listing->id,
            ], 201);
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
            'processing_status' => 'pending',
        ]);

        if ($type === Listing::TYPE_SELL && $imagePath) {
            $this->dispatchAiProcessing($request, $listing, $imagePath);
        }

        $message = match ($type) {
            Listing::TYPE_SELL => 'Listing saved. ReValue pricing will lock a fixed offer next — no bargaining after you accept.',
            Listing::TYPE_DONATE => 'Donation listed. Verified charities can now claim it. You will not pay for collection.',
            default => 'Item listed for recycling. Verified recyclers can arrange collection.',
        };

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', $message);
    }

    public function show(Request $request, Listing $listing): View|JsonResponse
    {
        if ($request->wantsJson()) {
            $this->authorize('view', $listing);

            return response()->json($listing->load('manualReview', 'priceOverrides'));
        }

        $listing->load(['user', 'donationClaim.organization']);

        return view('listings.show', [
            'listing' => $listing,
        ]);
    }

    public function acceptPrice(Request $request, Listing $listing): JsonResponse|RedirectResponse
    {
        if ($listing->user_id !== $request->user()->id) {
            abort_unless($request->wantsJson(), 403);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($listing->suggested_price === null) {
            $error = 'No suggested price available. Item requires manual review.';

            if ($request->wantsJson()) {
                return response()->json(['error' => $error], 422);
            }

            return back()->withErrors(['price' => $error]);
        }

        $listing->update([
            'final_price' => $listing->suggested_price,
            'status' => 'available',
        ]);

        $message = 'Price accepted. Item is now available for purchase.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'listing' => $listing,
            ]);
        }

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', $message);
    }

    public function requestReview(Request $request, Listing $listing): JsonResponse|RedirectResponse
    {
        if ($listing->user_id !== $request->user()->id) {
            abort_unless($request->wantsJson(), 403);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($listing->manualReview) {
            $error = 'Item is already under manual review.';

            if ($request->wantsJson()) {
                return response()->json(['error' => $error], 422);
            }

            return back()->withErrors(['price' => $error]);
        }

        ManualReview::create([
            'listing_id' => $listing->id,
            'status' => 'pending',
            'notes' => 'Seller request: '.($validated['reason'] ?? 'No reason provided'),
        ]);

        $listing->update(['status' => 'under_review']);

        $message = 'Your item has been submitted for manual pricing review. An admin will review it shortly.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', $message);
    }

    private function dispatchAiProcessing(Request $request, Listing $listing, ?string $imagePath): void
    {
        if (! $imagePath || ! $request->hasFile('image')) {
            return;
        }

        $imageContent = Storage::disk('public')->get($imagePath);
        $imageBase64 = base64_encode($imageContent);
        $mimeType = $request->file('image')->getMimeType();

        ProcessListingWithAI::dispatch($listing, $imageBase64, $mimeType);
    }
}
