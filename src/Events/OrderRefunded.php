<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;

/**
 * An order was refunded.
 */
final class OrderRefunded
{
    /**
     * @param  float  $amount  How much was refunded.
     * @param  string|null  $reason  Why the order was refunded.
     */
    public function __construct(
        public readonly Order $order,
        public readonly float $amount,
        public readonly ?string $reason = null,
    ) {
        //
    }
}
