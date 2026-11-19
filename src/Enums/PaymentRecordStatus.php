<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The lifecycle of a payment record.
 */
enum PaymentRecordStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Whether the payment has been settled.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::PAID, self::PARTIALLY_REFUNDED, self::REFUNDED], true);
    }

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PROCESSING => 'Procesando',
            self::PAID => 'Pagado',
            self::FAILED => 'Fallido',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::PARTIALLY_REFUNDED => 'Reembolso parcial',
        };
    }
}
