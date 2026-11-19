<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The fulfillment state of an order.
 */
enum FulfillmentStatus: string
{
    case UNFULFILLED = 'unfulfilled';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNFULFILLED => 'Sin preparar',
            self::PROCESSING => 'En preparación',
            self::SHIPPED => 'Enviada',
            self::DELIVERED => 'Entregada',
            self::RETURNED => 'Devuelta',
        };
    }
}
