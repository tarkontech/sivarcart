<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

use SivarcartCore\SivarcartCore\Models\Cart;

/**
 * The order needs a buyer and there is none.
 *
 * A buyer signs in with the guard of the package before placing an order, so
 * a cart without a buyer is a cart that cannot become an order.
 */
class CustomerRequired extends SivarcartCoreException
{
    /**
     * The cart does not belong to any buyer.
     */
    public static function forCart(Cart $cart): self
    {
        return new self(sprintf(
            'El carrito %s no pertenece a ningún comprador, así que no puede convertirse en una orden.',
            $cart->uuid,
        ));
    }

    /**
     * The buyer of the operation is not signed in.
     */
    public static function notSignedIn(): self
    {
        return new self('El comprador tiene que iniciar sesión para completar la orden.');
    }
}
