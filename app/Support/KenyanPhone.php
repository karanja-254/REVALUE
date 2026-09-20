<?php

namespace App\Support;

/**
 * Normalises the ways Kenyans actually type their Safaricom number
 * (0712345678, 712345678, 254712345678, +254712345678) into the
 * +2547XXXXXXXX / +2541XXXXXXXX form Paystack expects.
 */
class KenyanPhone
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        $local = match (true) {
            str_starts_with($digits, '254') => substr($digits, 3),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        // Safaricom/Airtel mobile lines are 9 digits starting with 7 or 1.
        if (! preg_match('/^[71]\d{8}$/', $local)) {
            return null;
        }

        return '+254'.$local;
    }

    public static function isValid(?string $phone): bool
    {
        return self::normalize($phone) !== null;
    }
}
