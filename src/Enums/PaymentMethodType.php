<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * Whether a payment method is collected through a gateway or paid offline.
 */
enum PaymentMethodType: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Pago en línea',
            self::OFFLINE => 'Pago fuera de línea',
        };
    }
}
