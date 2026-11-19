<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The payment state of an order.
 *
 * This is the order facing view of the payment. The payment record itself
 * keeps its own, more granular status in {@see PaymentRecordStatus}.
 */
enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Whether the order has been paid for.
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::PAID, self::PARTIALLY_REFUNDED], true);
    }

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Sin pagar',
            self::PENDING => 'Pago pendiente',
            self::PAID => 'Pagada',
            self::FAILED => 'Pago fallido',
            self::CANCELLED => 'Pago cancelado',
            self::REFUNDED => 'Reembolsada',
            self::PARTIALLY_REFUNDED => 'Reembolso parcial',
        };
    }
}
