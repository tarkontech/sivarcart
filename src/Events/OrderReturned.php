<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;

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
