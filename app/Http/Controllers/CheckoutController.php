<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(Request $request, Listing $listing, PaystackService $paystack): RedirectResponse
    {
        $buyer = $request->user();

        abort_if($listing->user_id === $buyer->id, 403, 'You cannot buy your own listing.');

        $order = DB::transaction(function () use ($listing, $buyer) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);

            if (! $listing->isSell()) {
                throw ValidationException::withMessages([
                    'checkout' => 'Only sell listings can be purchased.',
                ]);
            }

            if (! $listing->isAvailable()) {
                throw ValidationException::withMessages([
                    'checkout' => 'This item is no longer available.',
                ]);
            }

            $itemPrice = (float) ($listing->final_price ?? $listing->suggested_price);

            if ($itemPrice <= 0) {
                throw ValidationException::withMessages([
                    'checkout' => 'This listing does not have a locked ReValue price yet.',
                ]);
            }

            $active = Order::query()
                ->where('listing_id', $listing->id)
                ->whereIn('payment_status', [Order::PAYMENT_PENDING, Order::PAYMENT_PAID])
                ->lockForUpdate()
                ->get();

            if ($active->contains(fn (Order $existing) => $existing->buyer_id !== $buyer->id)) {
                throw ValidationException::withMessages([
                    'checkout' => 'This item is reserved by another buyer.',
                ]);
            }

            if ($active->contains(fn (Order $existing) => $existing->payment_status === Order::PAYMENT_PAID)) {
                throw ValidationException::withMessages([
                    'checkout' => 'This item is no longer available.',
                ]);
            }

            $order = $active->first(
                fn (Order $existing) => $existing->buyer_id === $buyer->id
                    && $existing->payment_status === Order::PAYMENT_PENDING
            );

            $deliveryFee = (float) config('revalue.fees.delivery');
            $serviceFee = (float) config('revalue.fees.service');
            $total = $itemPrice + $deliveryFee + $serviceFee;

            if ($order === null) {
                return Order::create([
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
            }

            if (! $order->payment_reference) {
                $order->update(['payment_reference' => $this->uniqueReference()]);
            }

            return $order->fresh();
        });

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
