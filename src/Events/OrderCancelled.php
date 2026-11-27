<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;

/**
 * An order was cancelled.
 */
final class OrderCancelled
{
    /**
     * @param  string|null  $reason  Why the order was cancelled.
     */
    public function __construct(
        public readonly Order $order,
        public readonly ?string $reason = null,
    ) {
        //
    }
}
