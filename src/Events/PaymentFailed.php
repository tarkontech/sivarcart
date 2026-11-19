<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;

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
