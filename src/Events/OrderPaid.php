<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Events;

use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;

/**
 * An order was paid.
 */
final class OrderPaid
{
    /**
     * @param  Payment|null  $payment  The payment that settled the order, when there is one.
     */
    public function __construct(
        public readonly Order $order,
        public readonly ?Payment $payment = null,
    ) {
        //
    }
}
