<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(Request $request, Order $order): View
    {
        $this->authorizeReview($request, $order);

        return view('reviews.create', [
            'order' => $order->load('listing'),
        ]);
    }

    public function store(StoreReviewRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeReview($request, $order);

        $order->review()->create([
            ...$request->validated(),
            'reviewer_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Thanks. Your rating helps keep ReValue handovers honest.');
    }

    private function authorizeReview(Request $request, Order $order): void
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);
        abort_unless($order->isCompleted(), 403, 'You can only rate a completed handover.');
        abort_if($order->review()->exists(), 403, 'This order already has a rating.');
    }
}
