<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;

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
