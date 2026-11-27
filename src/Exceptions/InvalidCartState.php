<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Exceptions;

use Tarkontech\SivarCart\Models\Cart;

/**
 * The cart cannot be used for the operation that was requested.
 */
class InvalidCartState extends SivarCartException
{
    /**
     * The cart does not hold any item.
     */
    public static function empty(): self
    {
        return new self('El carrito está vacío.');
    }

    /**
     * The cart was already turned into an order.
     */
    public static function alreadyConverted(Cart $cart): self
    {
        return new self(sprintf('El carrito %s ya fue convertido en una orden.', $cart->uuid));
    }
}
