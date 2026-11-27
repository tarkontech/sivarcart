<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;

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
