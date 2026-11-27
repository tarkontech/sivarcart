<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Payments;

use Illuminate\Support\Manager;
use Tarkontech\SivarCart\Contracts\PaymentGateway;

/**
 * Resolves the payment gateway that handles a payment method.
 *
 * A module that adds another gateway extends this manager and registers the
 * new driver in its own service provider.
 */
class PaymentGatewayManager extends Manager
{
    /**
     * The driver used when the payment method does not name one.
     */
    public function getDefaultDriver(): string
    {
        return (string) config('sivarcart.wompi.driver', 'wompi');
    }

    /**
     * Build the Wompi gateway.
     */
    public function createWompiDriver(): PaymentGateway
    {
        return new WompiPaymentGateway($this->container);
    }
}
