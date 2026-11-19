<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use SivarcartCore\SivarcartCore\Data\OrderTotals;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\Tax;

/**
 * Prices the lines of a cart or of an order.
 *
 * The service never writes anything: it receives the lines and returns what
 * they cost. Every amount is rounded to two decimals, and the totals are
 * always built from the rounded amounts, so the parts of a total always add
 * up to the total itself.
 *
 * @phpstan-type PriceLineInput array{
 *     unit_price: float,
 *     quantity: int,
 *     tax_rate: float
 * }
 * @phpstan-type PriceLineResult array{
 *     line_subtotal: float,
 *     discount_total: float,
 *     tax_total: float,
 *     line_total: float
 * }
 */
final class PricingService
{
    /**
     * Price every line of a cart or of an order.
     *
     * @param  list<PriceLineInput>  $lines
     * @param  float  $discountTotal  The discount to spread over the lines.
     * @return list<PriceLineResult>
     */
    public function price(array $lines, bool $taxInclusive, float $discountTotal = 0.0): array
    {
        $subtotals = [];

        foreach ($lines as $line) {
            $subtotals[] = round($line['unit_price'] * $line['quantity'], 2);
        }

        $subtotal = round(array_sum($subtotals), 2);
        $discounts = $this->distribute($subtotals, min($discountTotal, $subtotal));

        $results = [];

        foreach ($lines as $index => $line) {
            $lineSubtotal = $subtotals[$index];
            $lineDiscount = $discounts[$index];
            $base = round($lineSubtotal - $lineDiscount, 2);
            $rate = $line['tax_rate'];

            if ($taxInclusive) {
                $taxTotal = round($base - $base / (1 + $rate), 2);
                $lineTotal = $base;
            } else {
                $taxTotal = round($base * $rate, 2);
                $lineTotal = round($base + $taxTotal, 2);
            }

            $results[] = [
                'line_subtotal' => $lineSubtotal,
                'discount_total' => $lineDiscount,
                'tax_total' => $taxTotal,
                'line_total' => $lineTotal,
            ];
        }

        return $results;
    }

    /**
     * Build the totals of a cart or of an order.
     *
     * @param  list<PriceLineResult>  $lines
     * @param  float  $shippingTotal  What the shipping costs.
     * @param  float  $shippingDiscount  What the coupon takes off the shipping.
     */
    public function totals(
        array $lines,
        float $shippingTotal,
        float $shippingDiscount,
        bool $taxInclusive,
    ): OrderTotals {
        $subtotal = round(array_sum(array_column($lines, 'line_subtotal')), 2);
        $lineDiscount = round(array_sum(array_column($lines, 'discount_total')), 2);
        $taxTotal = round(array_sum(array_column($lines, 'tax_total')), 2);
        $discountTotal = round($lineDiscount + $shippingDiscount, 2);

        $total = $taxInclusive
            ? round($subtotal - $discountTotal + $shippingTotal, 2)
            : round($subtotal - $discountTotal + $taxTotal + $shippingTotal, 2);

        return new OrderTotals(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            taxTotal: $taxTotal,
            shippingTotal: round($shippingTotal, 2),
            total: $total,
            taxInclusive: $taxInclusive,
        );
    }

    /**
     * The tax rate that applies to a product.
     *
     * A product with an active tax pays that rate. Everything else pays the
     * default rate of the store.
     */
    public function taxRateFor(?Product $product, float $defaultRate): float
    {
        $tax = $product?->tax;

        if ($tax instanceof Tax && $tax->is_active) {
            return (float) $tax->rate;
        }

        return $defaultRate;
    }

    /**
     * Spread a discount over the lines, without losing a cent.
     *
     * Every line takes a share proportional to its subtotal and the cents
     * that are left are given to the lines with the biggest remainder.
     *
     * @param  list<float>  $subtotals
     * @return list<float>
     */
    private function distribute(array $subtotals, float $discountTotal): array
    {
        $totalCents = (int) round($discountTotal * 100);
        $subtotal = array_sum($subtotals);

        if ($totalCents <= 0 || $subtotal <= 0) {
            return array_fill(0, count($subtotals), 0.0);
        }

        $shares = [];
        $remainders = [];

        foreach ($subtotals as $index => $lineSubtotal) {
            $exact = ($lineSubtotal / $subtotal) * $totalCents;

            $shares[$index] = (int) floor($exact);
            $remainders[$index] = $exact - floor($exact);
        }

        $missing = $totalCents - array_sum($shares);

        if ($missing > 0) {
            arsort($remainders);

            $indexes = array_keys($remainders);
            $position = 0;

            while ($missing > 0) {
                $index = $indexes[$position % count($indexes)];
                $shares[$index]++;
                $missing--;
                $position++;
            }
        }

        return array_values(array_map(
            static fn (int $cents): float => $cents / 100,
            $shares,
        ));
    }
}
