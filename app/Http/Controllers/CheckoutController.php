<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateChargeRequiresRefundException;
use App\Exceptions\PaymentStillPendingException;
use App\Models\Listing;
use App\Models\Order;
use App\Services\OrderPaymentService;
use App\Services\PaystackService;
use App\Support\KenyanPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(Request $request, Listing $listing, PaystackService $paystack): RedirectResponse
    {
        $buyer = $request->user();

        abort_if($listing->user_id === $buyer->id, 403, 'You cannot buy your own listing.');

        // The buyer's email comes from their ReValue account; only the M-PESA
        // number is asked for, and the PIN is never typed into ReValue.
        $request->validate([
            'phone' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! KenyanPhone::isValid($value)) {
                    $fail('Enter a valid Kenyan M-PESA number, for example 0712345678.');
                }
            }],
        ], [], ['phone' => 'M-PESA phone number']);

        $phone = KenyanPhone::normalize($request->input('phone'));

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
            $charge = $paystack->chargeMpesa(
                $buyer->billingEmail(),
                $paystack->toSubunits($order->total_amount),
                $order->payment_reference,
                $phone,
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
                    : 'The M-PESA prompt could not be sent. Try again.',
            ]);
        }

        // Initiating an STK push proves nothing about payment. The order stays
        // pending until Paystack verification or the webhook says otherwise.
        return redirect()
            ->route('checkout.waiting', $order)
            ->with('mpesa', [
                'phone' => $phone,
                'display_text' => $charge['display_text'] ?? null,
            ]);
    }

    /**
     * "Check your phone" screen. Polls the status endpoint below.
     */
    public function waiting(Request $request, Order $order): View
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        return view('checkout.waiting', [
            'order' => $order->load('listing'),
            'mpesa' => session('mpesa', []),
        ]);
    }

    /**
     * Polled by the waiting page. Re-verifies with Paystack server-side, so a
     * missed webhook still cannot be faked from the browser.
     */
    public function status(Request $request, Order $order, OrderPaymentService $payments): JsonResponse
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        if ($order->payment_status === Order::PAYMENT_PENDING && $order->payment_reference) {
            try {
                $order = $payments->confirmReference($order->payment_reference);
            } catch (PaymentStillPendingException) {
                // Prompt is still on the customer's phone.
            } catch (DuplicateChargeRequiresRefundException $exception) {
                $order = $exception->order;
            } catch (Throwable $exception) {
                report($exception);
                $order = $order->fresh();
            }
        }

        return response()->json([
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'paid' => $order->payment_status === Order::PAYMENT_PAID,
            'redirect' => $order->payment_status === Order::PAYMENT_PAID
                ? route('orders.show', $order)
                : null,
        ]);
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'RV-'.strtoupper(Str::random(12));
        } while (Order::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }
}
