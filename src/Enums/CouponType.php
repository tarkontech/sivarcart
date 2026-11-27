<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

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
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.coupon_type.'.$this->value);
    }
}
