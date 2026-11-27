<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Tarkontech\SivarCart\Enums\CouponType;
use Tarkontech\SivarCart\Exceptions\CouponNotApplicable;
use Tarkontech\SivarCart\Models\Coupon;
use Tarkontech\SivarCart\Models\Order;

/**
 * Validates the discount codes and calculates what they are worth.
 *
 * The value of a percentage coupon is the percentage itself, so a coupon of
 * 15 gives a 15 % discount.
 */
final class CouponService
{
    /**
     * The coupon registered with that code.
     */
    public function find(string $code): ?Coupon
    {
        return Coupon::query()->where('code', $code)->first();
    }

    /**
     * Make sure the coupon can be applied to the order.
     *
     * @throws CouponNotApplicable
     */
    public function assertApplicable(Coupon $coupon, float $subtotal, ?int $customerId = null): void
    {
        if (! $coupon->isUsable()) {
            throw CouponNotApplicable::notUsable($coupon);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            throw CouponNotApplicable::belowMinimum($coupon);
        }

        $limit = $coupon->per_customer_limit;

        if ($limit !== null && $customerId !== null) {
            $used = Order::query()
                ->where('customer_id', $customerId)
                ->where('coupon_code', $coupon->code)
                ->count();

            if ($used >= $limit) {
                throw CouponNotApplicable::limitPerCustomerReached($coupon);
            }
        }
    }

    /**
     * What the coupon takes off the products.
     */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $discount = match ($coupon->type) {
            CouponType::FIXED => (float) $coupon->value,
            CouponType::PERCENTAGE => round($subtotal * ((float) $coupon->value / 100), 2),
            CouponType::FREE_SHIPPING => 0.0,
        };

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min(max($discount, 0.0), max($subtotal, 0.0)), 2);
    }

    /**
     * What the coupon takes off the shipping.
     */
    public function shippingDiscountFor(Coupon $coupon, float $shippingTotal): float
    {
        return $coupon->type === CouponType::FREE_SHIPPING ? round($shippingTotal, 2) : 0.0;
    }
}
