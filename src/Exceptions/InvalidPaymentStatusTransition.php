<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

use RuntimeException;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;

/**
 * Thrown when the payment of an order is asked to move to a state it cannot
 * reach.
 */
class InvalidPaymentStatusTransition extends RuntimeException
{
    public static function between(PaymentStatus $from, PaymentStatus $to): self
    {
        return new self(sprintf(
            'The payment of an order cannot move from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }
}
