<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Contracts;

use Tarkontech\SivarCart\Data\PaymentLinkResult;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;

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
