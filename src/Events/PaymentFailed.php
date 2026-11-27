<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;

/**
 * A payment of an order did not go through.
 */
final class PaymentFailed
{
    /**
     * @param  string  $reason  What the gateway reported.
     */
    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
        public readonly string $reason,
    ) {
        //
    }
}
