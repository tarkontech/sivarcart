<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Events;

use SivarcartCore\SivarcartCore\Models\Order;

/**
 * An order started being prepared.
 */
final class OrderPrepared
{
    public function __construct(
        public readonly Order $order,
    ) {
        //
    }
}
