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
     */
    public function label(): string
    {
        return match ($this) {
            self::FLAT_RATE => 'Tarifa fija',
            self::FREE => 'Gratis',
            self::PICKUP => 'Recoger en tienda',
        };
    }
}
