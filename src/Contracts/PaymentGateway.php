<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Contracts;

use SivarcartCore\SivarcartCore\Data\PaymentLinkResult;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;

/**
 * Creates the payment of an order on a payment gateway.
 *
 * The core ships Wompi. Another gateway is registered as a driver of the
 * payment gateway manager, without touching the rest of the package.
 */
interface PaymentGateway
{
    /**
     * Create the payment link the shopper has to pay.
     */
    public function createPaymentLink(Order $order, Payment $payment): PaymentLinkResult;
}
