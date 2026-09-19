<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a pickup/delivery PIN check or state transition is invalid.
 */
class PinVerificationException extends RuntimeException
{
    public static function invalidPin(): self
    {
        return new self('That PIN is incorrect. Please ask for the code again.');
    }

    public static function invalidState(string $detail): self
    {
        return new self($detail);
    }
}
