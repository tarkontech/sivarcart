<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The kind of discount a coupon applies.
 */
enum CouponType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case FREE_SHIPPING = 'free_shipping';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Monto fijo',
            self::PERCENTAGE => 'Porcentaje',
            self::FREE_SHIPPING => 'Envío gratis',
        };
    }
}
