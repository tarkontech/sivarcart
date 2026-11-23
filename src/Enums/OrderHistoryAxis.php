<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The axis of an order that a recorded change belongs to.
 *
 * The history of an order keeps two axes apart: the lifecycle of the order
 * and the state of its payment. A row of the history belongs to one of them.
 */
enum OrderHistoryAxis: string
{
    case STATUS = 'status';
    case PAYMENT = 'payment';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::STATUS => 'Estado de la orden',
            self::PAYMENT => 'Estado del pago',
        };
    }
}
