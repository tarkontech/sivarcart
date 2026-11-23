<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;

/**
 * An order came back to the store.
 */
final class OrderReturned
{
    /**
     * @param  string|null  $reason  Why the order came back.
     */
    public function __construct(
        public readonly Order $order,
        public readonly ?string $reason = null,
    ) {
        //
    }
}
