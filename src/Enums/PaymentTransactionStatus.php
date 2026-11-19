<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The outcome of a single gateway transaction.
 */
enum PaymentTransactionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FAILED = 'failed';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobada',
            self::REJECTED => 'Rechazada',
            self::FAILED => 'Fallida',
        };
    }
}
