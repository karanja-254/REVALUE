<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateChargeRequiresRefundException;
use App\Services\OrderPaymentService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaystackWebhookController extends Controller
{
    /**
     * Paystack server-to-server confirmation. Signature is required.
     */
    public function __invoke(Request $request, PaystackService $paystack, OrderPaymentService $payments): JsonResponse
    {
        $raw = $request->getContent();

        if (! $paystack->signatureIsValid($raw, $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);
        $reference = $data['reference'] ?? null;

        if ($event !== 'charge.success' || ! is_string($reference) || $reference === '') {
            return response()->json(['message' => 'Ignored']);
        }

        try {
            $payments->applyVerifiedPayload($reference, is_array($data) ? $data : []);
        } catch (DuplicateChargeRequiresRefundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'refund_required' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Verified']);
    }
}
