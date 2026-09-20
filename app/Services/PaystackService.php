<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackService
{
    public function initialize(string $email, int $amountInSubunits, string $reference, array $metadata = []): array
    {
        $response = $this->request()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amountInSubunits,
            'currency' => $this->currency(),
            'reference' => $reference,
            'callback_url' => config('revalue.paystack.callback_url'),
            'metadata' => $metadata,
        ]);

        $this->throwIfFailed($response, 'Paystack could not start checkout.');

        return $response->json('data');
    }

    /**
     * Kenya M-PESA charge. Paystack sends an STK prompt to the phone; the
     * customer types their M-PESA PIN on the handset, never in ReValue.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function chargeMpesa(string $email, int $amountInSubunits, string $reference, string $phone, array $metadata = []): array
    {
        $response = $this->request()->post('/charge', [
            'email' => $email,
            'amount' => $amountInSubunits,
            'currency' => $this->currency(),
            'reference' => $reference,
            'metadata' => $metadata,
            'mobile_money' => [
                'phone' => $phone,
                'provider' => 'mpesa',
            ],
        ]);

        $this->throwIfFailed($response, 'Paystack could not send the M-PESA prompt.');

        return $response->json('data');
    }

    public function verify(string $reference): array
    {
        $response = $this->request()->get('/transaction/verify/'.rawurlencode($reference));

        $this->throwIfFailed($response, 'Paystack could not verify this payment.');

        return $response->json('data');
    }

    public function signatureIsValid(string $payload, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, (string) config('revalue.paystack.secret_key'));

        return hash_equals($computed, $signature);
    }

    public function toSubunits(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function currency(): string
    {
        return (string) config('revalue.paystack.currency', 'KES');
    }

    private function request()
    {
        $secret = config('revalue.paystack.secret_key');

        if (! $secret) {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('revalue.paystack.base_url'), '/'))
            ->withToken($secret)
            ->acceptJson()
            ->timeout(20);
    }

    private function throwIfFailed(Response $response, string $message): void
    {
        if ($response->successful() && $response->json('status') === true) {
            return;
        }

        throw new RuntimeException($response->json('message') ?: $message);
    }
}
