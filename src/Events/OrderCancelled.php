<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;

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
