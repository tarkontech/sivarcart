<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Data;

/**
 * The cost of delivering an order.
 */
final class ShippingQuote
{
    /**
     * @param  float  $amount  What the shipping costs.
     * @param  int|null  $estimatedDays  How many days the delivery takes.
     */
    public function __construct(
        public readonly float $amount,
        public readonly ?int $estimatedDays = null,
    ) {
        //
    }
}
