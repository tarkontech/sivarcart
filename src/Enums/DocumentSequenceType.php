<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The kind of document a sequence counter tracks.
 */
enum DocumentSequenceType: string
{
    case ORDER = 'order';
    case PAYMENT = 'payment';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ORDER => 'Orden',
            self::PAYMENT => 'Pago',
        };
    }
}
