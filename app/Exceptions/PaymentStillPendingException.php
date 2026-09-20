<?php

namespace App\Exceptions;

use App\Models\Order;
use RuntimeException;

/**
 * The M-PESA prompt is still sitting on the customer's phone. Not a failure.
 */
class PaymentStillPendingException extends RuntimeException
{
    public function __construct(public Order $order)
    {
        parent::__construct('Waiting for the customer to authorise the M-PESA prompt.');
    }
}
