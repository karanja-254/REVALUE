<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerPayout;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPaymentService
{
    public function __construct(private PaystackService $paystack)
    {
    }

    /**
     * Verify with Paystack, then mark the order paid. Never trust the browser alone.
     */
    public function confirmReference(string $reference): Order
    {
        $payload = $this->paystack->verify($reference);

        return $this->applyVerifiedPayload($reference, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyVerifiedPayload(string $reference, array $payload): Order
    {
        [$order, $error] = DB::transaction(function () use ($reference, $payload) {
            $order = Order::query()
                ->where('payment_reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                throw new RuntimeException('No ReValue order matches that Paystack reference.');
            }

            if ($order->payment_status === Order::PAYMENT_PAID) {
                return [$order->load(['listing', 'buyer', 'sellerPayout']), null];
            }

            $status = $payload['status'] ?? null;
            $amount = (int) ($payload['amount'] ?? 0);
            $currency = strtoupper((string) ($payload['currency'] ?? ''));

            if ($status !== 'success') {
                $this->failOrder($order, $reference, $payload);

                return [$order->fresh(), 'Paystack reports this transaction was not successful.'];
            }

            if ($amount !== $this->paystack->toSubunits($order->total_amount)) {
                $this->failOrder($order, $reference, $payload);

                return [$order->fresh(), 'Paid amount does not match the locked ReValue total.'];
            }

            if ($currency !== $this->paystack->currency()) {
                $this->failOrder($order, $reference, $payload);

                return [$order->fresh(), 'Paid currency does not match ReValue.'];
            }

            $listing = Listing::query()->lockForUpdate()->findOrFail($order->listing_id);

            if (! $listing->isSell() || $listing->status === Listing::STATUS_SOLD) {
                $this->failOrder($order, $reference, $payload);

                return [$order->fresh(), 'This listing is no longer available to buy.'];
            }

            $this->recordPayment($order, $reference, $payload, Payment::STATUS_PAID);

            $order->update([
                'payment_status' => Order::PAYMENT_PAID,
                'order_status' => Order::STATUS_PAID,
                'pickup_pin' => $order->pickup_pin ?: (string) random_int(1000, 9999),
                'delivery_pin' => $order->delivery_pin ?: (string) random_int(1000, 9999),
            ]);

            $listing->update(['status' => Listing::STATUS_SOLD]);

            SellerPayout::query()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'seller_id' => $listing->user_id,
                    'amount' => $order->item_price,
                    'status' => SellerPayout::STATUS_PENDING,
                ]
            );

            return [$order->fresh(['listing', 'buyer', 'sellerPayout']), null];
        });

        if ($error !== null) {
            throw new RuntimeException($error);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function failOrder(Order $order, string $reference, array $payload): void
    {
        $this->recordPayment($order, $reference, $payload, Payment::STATUS_FAILED);
        $order->update(['payment_status' => Order::PAYMENT_FAILED]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordPayment(Order $order, string $reference, array $payload, string $status): void
    {
        Payment::query()->updateOrCreate(
            ['reference' => $reference],
            [
                'order_id' => $order->id,
                'provider' => 'paystack',
                'amount' => ((int) ($payload['amount'] ?? 0)) / 100,
                'currency' => $payload['currency'] ?? $this->paystack->currency(),
                'status' => $status,
                'channel' => $payload['channel'] ?? null,
                'payload' => $payload,
                'paid_at' => $status === Payment::STATUS_PAID ? now() : null,
            ]
        );
    }
}
