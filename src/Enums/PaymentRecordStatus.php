<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

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
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.payment_record_status.'.$this->value);
    }
}
