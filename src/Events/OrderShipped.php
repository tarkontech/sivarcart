<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;

/**
 * An order was shipped.
 */
final class OrderShipped
{
    public function __construct(
        public readonly Order $order,
    ) {
        //
    }
}
