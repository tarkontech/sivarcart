<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Exceptions;

use Tarkontech\SivarCart\Models\Coupon;

/**
 * The coupon cannot be applied to the order.
 */
class CouponNotApplicable extends SivarCartException
{
    /**
     * No coupon is registered with that code.
     */
    public static function unknownCode(string $code): self
    {
        return new self(sprintf('El cupón "%s" no existe.', $code));
    }

    /**
     * The coupon exists but cannot be used right now.
     */
    public static function notUsable(Coupon $coupon): self
    {
        return new self(sprintf('El cupón "%s" no se puede aplicar.', $coupon->code));
    }

    /**
     * The order does not reach the minimum amount of the coupon.
     */
    public static function belowMinimum(Coupon $coupon): self
    {
        return new self(sprintf(
            'El cupón "%s" requiere una orden de al menos %s.',
            $coupon->code,
            number_format((float) $coupon->min_order_amount, 2),
        ));
    }

    /**
     * The customer already used the coupon as many times as allowed.
     */
    public static function limitPerCustomerReached(Coupon $coupon): self
    {
        return new self(sprintf('El cupón "%s" ya fue usado el máximo de veces permitido.', $coupon->code));
    }
}
