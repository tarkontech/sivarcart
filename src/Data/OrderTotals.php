<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Data;

/**
 * The totals of a cart or of an order.
 *
 * The tax total is informative when the prices include the tax: it is the
 * portion of the total that belongs to the tax authority and it is already
 * part of the subtotal.
 */
final class OrderTotals
{
    /**
     * @param  float  $subtotal  The sum of the lines before any discount.
     * @param  float  $discountTotal  The discount applied to the lines and to the shipping.
     * @param  float  $taxTotal  The tax of the lines.
     * @param  float  $shippingTotal  The cost of the shipping.
     * @param  float  $total  What the shopper has to pay.
     * @param  bool  $taxInclusive  Whether the prices of the catalog include the tax.
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discountTotal,
        public readonly float $taxTotal,
        public readonly float $shippingTotal,
        public readonly float $total,
        public readonly bool $taxInclusive,
    ) {
        //
    }
}
