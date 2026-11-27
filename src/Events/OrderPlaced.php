<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;

/**
 * An order was placed by a shopper.
 */
final class OrderPlaced
{
    public function __construct(public readonly Order $order)
    {
        //
    }
}
