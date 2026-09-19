<?php

namespace App\Exceptions;

use App\Models\Order;
use RuntimeException;

class DuplicateChargeRequiresRefundException extends RuntimeException
{
    public function __construct(
        public Order $order,
        string $message = 'Paystack charged this buyer, but another buyer already won the listing. This successful charge requires a refund.',
    ) {
        parent::__construct($message);
    }
}
