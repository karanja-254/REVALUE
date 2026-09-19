<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(Request $request, Listing $listing, PaystackService $paystack): RedirectResponse
    {
        $buyer = $request->user();

        abort_unless($listing->isSell(), 422, 'Only sell listings can be purchased.');
        abort_unless($listing->isAvailable(), 422, 'This item is no longer available.');
        abort_if($listing->user_id === $buyer->id, 403, 'You cannot buy your own listing.');

        $itemPrice = (float) ($listing->final_price ?? $listing->suggested_price);

        abort_if($itemPrice <= 0, 422, 'This listing does not have a locked ReValue price yet.');

        $deliveryFee = (float) config('revalue.fees.delivery');
        $serviceFee = (float) config('revalue.fees.service');
        $total = $itemPrice + $deliveryFee + $serviceFee;

        $order = Order::query()
            ->where('listing_id', $listing->id)
            ->where('buyer_id', $buyer->id)
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->latest()
            ->first();

        if ($order === null) {
            $order = Order::create([
                'listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'item_price' => $itemPrice,
                'delivery_fee' => $deliveryFee,
                'service_fee' => $serviceFee,
                'total_amount' => $total,
                'payment_reference' => $this->uniqueReference(),
                'payment_status' => Order::PAYMENT_PENDING,
                'order_status' => Order::STATUS_PENDING_PAYMENT,
            ]);
        } elseif (! $order->payment_reference) {
            $order->update(['payment_reference' => $this->uniqueReference()]);
        }

        try {
            $session = $paystack->initialize(
                $buyer->email,
                $paystack->toSubunits($order->total_amount),
                $order->payment_reference,
                [
                    'order_id' => $order->id,
                    'listing_id' => $listing->id,
                    'buyer_id' => $buyer->id,
                ]
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'checkout' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Paystack checkout could not be started. Try again.',
            ]);
        }

        return redirect()->away($session['authorization_url']);
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'RV-'.strtoupper(Str::random(12));
        } while (Order::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }
}
