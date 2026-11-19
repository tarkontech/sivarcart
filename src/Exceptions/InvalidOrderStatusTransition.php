<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

use RuntimeException;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;

/**
 * Thrown when an order is asked to move to a status it cannot reach.
 */
class InvalidOrderStatusTransition extends RuntimeException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self(sprintf(
            'An order cannot move from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }
}
