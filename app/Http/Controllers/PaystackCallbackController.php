<?php

namespace App\Http\Controllers;

use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class PaystackCallbackController extends Controller
{
    /**
     * Paystack redirects the buyer here. We still verify the reference server-side.
     */
    public function __invoke(Request $request, OrderPaymentService $payments): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()
                ->route('orders.failed')
                ->with('status', 'Paystack did not return a payment reference.');
        }

        try {
            $order = $payments->confirmReference($reference);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('orders.failed')
                ->with('status', $exception->getMessage());
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Payment verified. The ReValue price is locked and logistics can now schedule pickup.');
    }
}
