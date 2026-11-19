<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Data\ShippingQuote;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * Quotes the shipping with the price the store configured for the method.
 */
final class ShippingMethodRateCalculator implements ShippingRateCalculator
{
    /**
     * Quote the shipping of a cart.
     *
     * @param  array<string, mixed>  $shippingAddress  Where the order has to be delivered.
     */
    public function quote(ShippingMethod $method, Cart $cart, array $shippingAddress): ShippingQuote
    {
        $amount = match ($method->type) {
            ShippingMethodType::FLAT_RATE => (float) $method->price,
            ShippingMethodType::FREE, ShippingMethodType::PICKUP => 0.0,
        };

        return new ShippingQuote($amount, $method->estimated_days);
    }
}
