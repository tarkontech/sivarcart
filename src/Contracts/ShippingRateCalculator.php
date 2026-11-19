<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Contracts;

use SivarcartCore\SivarcartCore\Data\ShippingQuote;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * Calculates what the delivery of an order costs.
 *
 * The core ships a flat rate calculator. A module that knows how to deliver
 * to every department of the country replaces this binding.
 */
interface ShippingRateCalculator
{
    /**
     * Quote the shipping of a cart.
     *
     * @param  array<string, mixed>  $shippingAddress  Where the order has to be delivered.
     */
    public function quote(ShippingMethod $method, Cart $cart, array $shippingAddress): ShippingQuote;
}
