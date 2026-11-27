<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Exceptions;

use Throwable;

/**
 * The payment gateway refused to create the payment.
 */
class PaymentGatewayFailed extends SivarCartException
{
    /**
     * Wrap the error the gateway reported.
     */
    public static function from(Throwable $previous): self
    {
        return new self(
            sprintf('La pasarela de pago falló: %s', $previous->getMessage()),
            0,
            $previous,
        );
    }
}
