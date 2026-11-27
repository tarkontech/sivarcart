<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Tarkontech\SivarCart\Contracts\ShippingRateCalculator;
use Tarkontech\SivarCart\Data\ShippingQuote;
use Tarkontech\SivarCart\Enums\ShippingMethodType;
use Tarkontech\SivarCart\Models\Cart;
use Tarkontech\SivarCart\Models\ShippingMethod;

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
