<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The kind of shipping method offered by the store.
 */
enum ShippingMethodType: string
{
    case FLAT_RATE = 'flat_rate';
    case FREE = 'free';
    case PICKUP = 'pickup';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.shipping_method_type.'.$this->value);
    }
}
